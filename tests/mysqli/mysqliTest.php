<?php

namespace ezsql\Tests\mysqli;

use ezsql\Database;
use ezsql\Config;
use ezsql\Database\ez_mysqli;
use ezsql\Tests\EZTestCase;

class mysqliTest extends EZTestCase 
{
    
    /**
     * constant string database port
     */
    const TEST_DB_PORT = '3306';

    /**
     * @var ez_mysqli
     */
    protected $object;
    
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
	{
        if (!extension_loaded('mysqli')) {
            $this->markTestSkipped(
              'The MySQLi extension is not available.'
            );
        }

        $this->object = Database::initialize('mysqli', [self::TEST_DB_USER, self::TEST_DB_PASSWORD, self::TEST_DB_NAME]);
        $this->object->prepareOn();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void 
    {
        if ($this->object->isConnected()) {
            $this->object->select(self::TEST_DB_NAME);
            $this->assertEquals($this->object->drop('unit_test'), 0);
        }
        $this->object = null;
    }

    /**
     * @covers ezsql\Database\ez_mysqli::settings
     */
    public function testSettings()
    {
        $this->assertTrue($this->object->settings() instanceof \ezsql\ConfigInterface);    
    } 

    /**
     * @covers ezsql\Database\ez_mysqli::quick_connect
     */
    public function testQuick_connect() 
    {
        $result = $this->object->quick_connect();

        $this->assertTrue($result);
    }

    /**
     * @covers ezsql\Database\ez_mysqli::quick_connect
     */
    public function testQuick_connect_full() 
    {
        $result = $this->object->quick_connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD, self::TEST_DB_NAME, self::TEST_DB_HOST, self::TEST_DB_PORT, self::TEST_DB_CHARSET);

        $this->assertTrue($result);
    }

    /**
     * @covers ezsql\Database\ez_mysqli::connect
     */
    public function testConnect() 
    {        
        $this->errors = array();
        set_error_handler(array($this, 'errorHandler')); 
         
        $this->assertFalse($this->object->connect('no',''));  
        $this->assertFalse($this->object->connect('self::TEST_DB_USER', 'self::TEST_DB_PASSWORD',' self::TEST_DB_HOST', 'self::TEST_DB_PORT'));  
        $result = $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);

        $this->assertTrue($result);
    }

    /**
     * @covers ezsql\Database\ez_mysqli::select
     * @covers ezsql\Database\ez_mysqli::reset
     */
    public function testSelect() 
    {
        $this->object->connect();
        $this->assertTrue($this->object->isConnected());

        $result = $this->object->select(self::TEST_DB_NAME);

        $this->assertTrue($result);

        $this->errors = array();
        set_error_handler(array($this, 'errorHandler')); 
        $this->assertTrue($this->object->select(''));
        $this->object->disconnect();
        $this->assertFalse($this->object->select('notest'));        
        $this->object->connect();
        $this->object->reset();
        $this->assertFalse($this->object->select(self::TEST_DB_NAME));
        $this->object->connect();
        $this->assertFalse($this->object->select('notest'));
        $this->assertTrue($this->object->select(self::TEST_DB_NAME));        
    } // testSelect

    /**
     * @covers ezsql\Database\ez_mysqli::escape
     */
    public function testEscape() 
    {
        $this->object->connect();
        $result = $this->object->escape("This is'nt escaped.");

        $this->assertEquals("This is\\'nt escaped.", $result);
    } // testEscape

    /**
     * @covers ezsql\Database\ez_mysqli::sysDate
     */
    public function testSysDate() 
    {
        $this->assertEquals('NOW()', $this->object->sysDate());
    } 

    /**
     * @covers ezsql\Database\ez_mysqli::query
     * @covers ezsql\Database\ez_mysqli::processQueryResult
     */
    public function testQueryInsert() 
    {
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);

        $this->object->select(self::TEST_DB_NAME);

        $this->assertEquals(0, $this->object->query('CREATE TABLE unit_test(id integer, test_key varchar(50), PRIMARY KEY (ID))'));

        $this->assertEquals(1, $this->object->query('INSERT INTO unit_test(id, test_key) VALUES(1, \'test 1\')'));
        
        $this->object->reset();
        $this->assertEquals(1, $this->object->query('INSERT INTO unit_test(id, test_key) VALUES(2, \'test 2\')'));

        $this->object->disconnect();
        $this->assertFalse($this->object->isConnected());        
    }

    /**
     * @covers ezsql\Database\ez_mysqli::query
     * @covers ezsql\Database\ez_mysqli::processQueryResult
     */
    public function testQuerySelect() 
    {
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);

        $this->object->select(self::TEST_DB_NAME);
        
        $this->assertEquals($this->object->query('DROP TABLE IF EXISTS unit_test'), 0); 
        $this->assertEquals($this->object->query('CREATE TABLE unit_test(id integer, test_key varchar(50), PRIMARY KEY (ID))'), 0);

        $this->assertEquals($this->object->query('INSERT INTO unit_test(id, test_key) VALUES(1, \'test 1\')'), 1);
        $this->assertEquals($this->object->query('INSERT INTO unit_test(id, test_key) VALUES(2, \'test 2\')'), 1);
        $this->assertEquals($this->object->query('INSERT INTO unit_test(id, test_key) VALUES(3, \'test 3\')'), 1);

        $result = $this->object->query('SELECT * FROM unit_test');

        $i = 1;
        foreach ($this->object->get_results() as $row) {
            $this->assertEquals($i, $row->id);
            $this->assertEquals('test ' . $i, $row->test_key);
            ++$i;
        }
    }

    /**
     * @covers ezsql\ezsqlModel::get_results
     */
    public function testGet_results() 
    {         
        $this->assertEquals($this->object->query('CREATE TABLE unit_test(id integer, test_key varchar(50), PRIMARY KEY (ID))'), 0);

        $this->assertEquals($this->object->query('INSERT INTO unit_test(id, test_key) VALUES(1, \'test 1\')'), 1);
        $this->assertEquals($this->object->query('INSERT INTO unit_test(id, test_key) VALUES(2, \'test 2\')'), 1);
        $this->assertEquals($this->object->query('INSERT INTO unit_test(id, test_key) VALUES(3, \'test 3\')'), 1);

        $this->object->query('SELECT * FROM unit_test');
        $result = $this->object->get_results('SELECT * FROM unit_test', _JSON);
                
        $this->assertEquals('[{"id":"1","test_key":"test 1"},{"id":"2","test_key":"test 2"},{"id":"3","test_key":"test 3"}]', $result);

    }

    /**
     * @covers ezsql\Database\ez_mysqli::getHost
     */
    public function testGetHost() 
    {
        $this->assertEquals(self::TEST_DB_HOST, $this->object->getHost());
    }

    /**
     * @covers ezsql\Database\ez_mysqli::getPort
     */
    public function testGetPort() 
    {
        $this->assertEquals(self::TEST_DB_PORT, $this->object->getPort());
    }

    /**
     * @covers ezsql\Database\ez_mysqli::getCharset
     */
    public function testGetCharset() 
    {
        $this->assertEquals(self::TEST_DB_CHARSET, $this->object->getCharset());
    } // testGetCharset
    
    /**
     * @covers ezsql\Database\ez_mysqli::disconnect
     * @covers ezsql\Database\ez_mysqli::reset
     * @covers ezsql\Database\ez_mysqli::handle
     */
    public function testDisconnect() 
    {
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);
        $this->object->select(self::TEST_DB_NAME);
        $this->assertNotNull($this->object->handle());
        $this->object->disconnect();
        $this->assertFalse($this->object->isConnected());
        $this->object->reset();
        $this->assertNull($this->object->handle());
    } // testDisconnect

    /**
     * @covers ezsql\Database\ez_mysqli::getInsertId
     */
    public function testGetInsertId() 
    {
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);
        $this->object->select(self::TEST_DB_NAME);

        $this->assertEquals($this->object->query('CREATE TABLE unit_test(id int(11) NOT NULL AUTO_INCREMENT, test_key varchar(50), PRIMARY KEY (ID))ENGINE=MyISAM  DEFAULT CHARSET=utf8'), 0);
        $this->assertEquals($this->object->query('INSERT INTO unit_test(id, test_key) VALUES(1, \'test 1\')'), 1);

        $this->assertEquals(1, $this->object->getInsertId());
    } // testInsertId

    /**
     * @covers ezsql\ezQuery::create
     */
    public function testCreate()
    {
        $object = \mysqlInstance([self::TEST_DB_USER, self::TEST_DB_PASSWORD, self::TEST_DB_NAME]);
        $this->assertEquals($this->object, $object);             
        $object->prepareOff();
        $this->assertEquals($object->create('create_test',
            \column('id', INTR, 11, \AUTO),
            \column('create_key', VARCHAR, 50),
            \primary('id_pk', 'id')), 
        0);

        $this->assertEquals(1, $object->insert('create_test',
            ['create_key' => 'test 2']));
    }

    /**
     * @covers ezsql\ezQuery::drop
     */
    public function testDrop()
    {             
        $this->assertEquals($this->object->drop('create_test'), 0);
    }
   
    /**
     * @covers ezsql\ezQuery::insert
     * @covers ezsql\ezQuery::create
     * @covers ezsql\Database::initialize
     * @covers ezsql\Database\ez_mysqli::query
     * @covers ezsql\Database\ez_mysqli::processQueryResult
     * @covers ezsql\Database\ez_mysqli::query_prepared
     * @covers ezsql\Database\ez_mysqli::prepareValues
     * @covers \column
     * @covers \primary
     */
    public function testInsert()
    {
        $object = Database::initialize('mysqli', [self::TEST_DB_USER, self::TEST_DB_PASSWORD, self::TEST_DB_NAME]);
        $this->assertEquals($this->object, $object);        
        $object->connect();
        $object->create('unit_test',
            \column('id', INTR, 11, \AUTO),
            \column('test_key', VARCHAR, 50),
            \primary('id_pk', 'id')
        );
        $this->assertEquals(1, $this->object->insert('unit_test', array('test_key'=>'test 2' )));
    }
        
    /**
     * @covers ezsql\ezQuery::replace
     */
    public function testReplace()
    {
        $this->object->prepareOff();
        $this->object->query('CREATE TABLE unit_test(id int(11) NOT NULL AUTO_INCREMENT, test_key varchar(50), PRIMARY KEY (ID))ENGINE=MyISAM  DEFAULT CHARSET=utf8');
        $this->object->insert('unit_test', array('id'=>2, 'test_key'=>'test 2' ));
        $this->assertEquals($this->object->replace('unit_test', array('id'=>2, 'test_key'=>'test 3' )), 2);
    }
    
    /**
     * @covers ezsql\ezQuery::update
     */
    public function testUpdate()
    {
        $this->object->prepareOff();
        $this->object->query('CREATE TABLE unit_test(id int(11) NOT NULL AUTO_INCREMENT, test_key varchar(50), PRIMARY KEY (ID))ENGINE=MyISAM  DEFAULT CHARSET=utf8');

        $this->object->insert('unit_test', array('id' => 11, 'test_key' => 'testUpdate() 11' ));
        $this->object->insert('unit_test', array('id' => 12, 'test_key' => 'testUpdate() 12' ));
        $this->object->insert('unit_test', array('id '=> 13, 'test_key' => 'testUpdate() 13' ));

        $unit_test['test_key'] = 'testing testUpdate()';
        $where = ['id', '=', 11];
        $this->assertEquals(1, $this->object->update('unit_test', $unit_test, $where));

        $this->assertEquals(1, 
            $this->object->update('unit_test', $unit_test, 	['test_key', EQ, 'testUpdate() 13', 'and'], ['id', '=', 13])
        );

        $this->assertEquals(0, 
            $this->object->update('unit_test', $unit_test, eq('id', 14))
        );

        $this->assertEquals(1, 
            $this->object->update('unit_test', $unit_test, eq('test_key', 'testUpdate() 12'), ['id', '=', 12])
        );

        $this->assertEquals(0, $this->object->drop('unit_test'));
    }
    
    /**
     * @covers ezsql\ezQuery::delete
     */
    public function testDelete()
    {
        $this->object->prepareOff();
        $this->object->query('CREATE TABLE unit_test(id int(11) NOT NULL AUTO_INCREMENT, test_key varchar(50), PRIMARY KEY (ID))ENGINE=MyISAM  DEFAULT CHARSET=utf8');

        $unit_test['id'] = 1;
        $unit_test['test_key'] = 'testDelete() 11';
        $this->object->insert('unit_test', $unit_test );

        $unit_test['id'] = 2;
        $unit_test['test_key'] = 'testDelete() 12';
        $this->object->insert('unit_test', $unit_test );

        $unit_test['id'] = 3;
        $unit_test['test_key'] = 'testDelete() 13';
        $this->object->insert('unit_test', $unit_test );

        $this->assertEquals(1, $this->object->delete('unit_test', ['id', '=', 1]));

        $this->assertEquals(1, $this->object->delete('unit_test', 
            ['test_key', '=', $unit_test['test_key'], 'and'], ['id','=', 3]));

        $where = 1;
        $this->assertEquals(0, $this->object->delete('unit_test', ['test_key', '=', $where]));

        $where = ['id', '=', 2];
        $this->assertEquals(1, $this->object->delete('unit_test', $where));
    }  
       
    /**
     * @covers ezsql\ezQuery::selecting
     */
    public function testSelecting()
    {
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);
        $this->object->select(self::TEST_DB_NAME);
        $this->object->query('CREATE TABLE unit_test(id int(11) NOT NULL AUTO_INCREMENT, test_key varchar(50), PRIMARY KEY (ID))ENGINE=MyISAM  DEFAULT CHARSET=utf8');

        $this->object->insert('unit_test', array('id'=>1, 'test_key'=>'testing 1' ));
        $this->object->insert('unit_test', array('id'=>2, 'test_key'=>'testing 2' ));
        $this->object->insert('unit_test', array('id'=>3, 'test_key'=>'testing 3' ));
        
        $result = $this->object->selecting('unit_test');

        $i = 1;
        foreach ($result as $row) {
            $this->assertEquals($i, $row->id);
            $this->assertEquals('testing ' . $i, $row->test_key);
            ++$i;
        }
        
        $where=['test_key','=','testing 2'];
        $result = $this->object->selecting('unit_test', 'id', $where);
        foreach ($result as $row) {
            $this->assertEquals(2, $row->id);
        }
        
        $result = $this->object->selecting('unit_test', 'test_key', ['id','=',3 ]);
        foreach ($result as $row) {
            $this->assertEquals('testing 3', $row->test_key);
        }
        
        $result = $this->object->selecting('unit_test', array ('test_key'), "id  =  1");
        foreach ($result as $row) {
            $this->assertEquals('testing 1', $row->test_key);
        }
    }

    /**
     * @covers ezsql\Database\ez_mysqli::query
     * @covers ezsql\Database\ez_mysqli::processQueryResult
     * @covers ezsql\Database\ez_mysqli::query_prepared
     * @covers ezsql\Database\ez_mysqli::fetch_prepared_result
     * @covers ezsql\Database\ez_mysqli::prepareValues
     * @covers ezsql\ezQuery::create
     * @covers ezsql\ezQuery::insert
     * @covers ezsql\ezQuery::selecting
     * @covers ezsql\ezQuery::drop
     * @covers ezsql\ezsqlModel::debug
     * @covers ezsql\ezsqlModel::queryResult
     */
    public function testSelectingAndCreateTable()
    {
        $this->object->drop('users');
        $this->object->create('users',
            column('id', INTR, 11, PRIMARY),
            column('tel_num', INTR, 32, notNULL),
            column('user_name ', VARCHAR, 128),
            column('email', CHAR, 50)
        );

        $this->assertEquals(0, $this->object->insert('users', ['id'=> 1, 
            'tel_num' => 123456, 
            'email' => 'walker@email.com', 
            'user_name ' => 'walker'])
        );

        $this->assertEquals(0, $this->object->insert('users', ['id'=> 2, 
            'tel_num' => 654321, 
            'email' => 'email@host.com', 
            'user_name ' => 'email'])
        );

        $this->assertEquals(0, $this->object->insert('users', ['id'=> 3, 
            'tel_num' => 456123, 
            'email' => 'host@email.com', 
            'user_name ' => 'host'])
        );
                
        $result = $this->object->selecting('users', 'id, tel_num, email', eq('user_name ', 'walker'));

        $this->object->setDebug_Echo_Is_On(true); 
        $this->expectOutputRegex('/[123456]/');
        $this->expectOutputRegex('/[walker@email.com]/');
        $this->object->debug();
        
        foreach ($result as $row) {
            $this->assertEquals(1, $row->id);
            $this->assertEquals(123456, $row->tel_num);
            $this->assertEquals('walker@email.com', $row->email);
        }

        $this->object->drop('users');
    }

    /**
     * @covers ezsql\ezQuery::create_select
     */
    public function testCreate_select()
    {
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);
        $this->object->select(self::TEST_DB_NAME);
        $this->object->query('CREATE TABLE unit_test(id int(11) NOT NULL AUTO_INCREMENT, test_key varchar(50), PRIMARY KEY (ID))ENGINE=MyISAM  DEFAULT CHARSET=utf8');

        $this->object->insert('unit_test', array('id'=>'1', 'test_key'=>'testing 1' ));
        $this->object->insert('unit_test', array('id'=>'2', 'test_key'=>'testing 2' ));
        $this->object->insert('unit_test', array('id'=>'3', 'test_key'=>'testing 3' ));
        
        $this->assertEquals(0, $this->object->create_select('new_new_test','*','unit_test'));
        
        $result = $this->object->selecting('new_new_test');

        $i = 1;
        foreach ($result as $row) {
            $this->assertEquals($i, $row->id);
            $this->assertEquals('testing ' . $i, $row->test_key);
            ++$i;
        }

        $this->assertEquals(0, $this->object->drop('new_new_test'));    
    }    
              
    /**
     * @covers ezsql\ezQuery::insert_select
     */
    public function testInsert_select()
    {
        $this->object->query('CREATE TABLE unit_test(id int(11) NOT NULL AUTO_INCREMENT, test_key varchar(50), PRIMARY KEY (ID))ENGINE=MyISAM  DEFAULT CHARSET=utf8');

        $this->object->insert('unit_test', array('id'=>'1', 'test_key'=>'testing 1' ));
        $this->object->insert('unit_test', array('id'=>'2', 'test_key'=>'testing 2' ));
        $this->object->insert('unit_test', array('id'=>'3', 'test_key'=>'testing 3' ));
        
        $this->assertEquals($this->object->drop('new_select_test'), 0);

        $this->object->query('CREATE TABLE new_select_test(id int(11) NOT NULL AUTO_INCREMENT, test_key varchar(50), PRIMARY KEY (ID))ENGINE=MyISAM  DEFAULT CHARSET=utf8');
		
        $this->assertEquals($this->object->insert_select('new_select_test', '*', 'unit_test'), 3);
        
        $result = select('new_select_test');
        
        $i = 1;
        foreach ($result as $row) {
            $this->assertEquals($i, $row->id);
            $this->assertEquals('testing ' . $i, $row->test_key);
            ++$i;
        }

        $this->assertEquals($this->object->drop('new_select_test'), 0);
    }    
	
    /**
     * @covers ezsql\ezQuery::where
     * @covers ezsql\ezQuery::conditionIs
     * @covers ezsql\ezQuery::conditionBetween
     * @covers ezsql\ezQuery::conditions
     * @covers ezsql\ezQuery::conditionIn
     * @covers ezsql\ezQuery::processConditions
     * @covers \where
     */
    public function testWhere()
    {
        $this->object->prepareOff();
        $expect = where(
            where(between('where_test', 'testing 1', 'testing 2'),
            like('test_null', 'null'))
        );
            
        $this->assertContains('WHERE where_test BETWEEN \'testing 1\' AND \'testing 2\' AND test_null IS NULL', $expect);

        $this->assertFalse(where(
            array('where_test', 'bad', 'testing 1', 'or'),
			array('test_null', 'like', 'null')
            ));
            
        $this->object->prepareOn();
        $expect = where(
            between('where_test', 'testing 1', 'testing 2', 'bad'),
			like('test_null', 'null')
		);

        $this->assertContains('WHERE where_test BETWEEN '._TAG.' AND '._TAG.' AND test_null IS NULL', $expect);   
    }     
 
    /**
     * @covers ezsql\ezQuery::drop
     * @covers ezsql\ezQuery::create
     * @covers ezsql\Database\ez_mysqli::query
     * @covers ezsql\Database\ez_mysqli::query_prepared
     * @covers ezsql\Database\ez_mysqli::fetch_prepared_result
     * @covers ezsql\Database\ez_mysqli::prepareValues
     * @covers ezsql\Database\ez_mysqli::processQueryResult
     * @covers ezsql\ezsqlModel::queryResult
     */
    public function testQuery_prepared() {
        $this->object->prepareOff();
        $this->object->drop('prepare_test');
        $this->object->create('prepare_test',
            column('id', INTR, 11, notNULL, PRIMARY),
            column('prepare_key', VARCHAR, 50),
            column('prepare_price', DECIMAL, 12, 2)
        );

        $this->object->query_prepared('INSERT INTO prepare_test( id, prepare_key, prepare_price ) VALUES( ?, ?, ? )', 
            [ 9, 'test 1', 7.12]);

        $this->object->query_prepared('INSERT INTO prepare_test( id, prepare_key, prepare_price ) VALUES( ?, ?, ? )', 
            [ 3, 'test 21', 44.01]);

        $this->object->query_prepared('INSERT INTO prepare_test( id, prepare_key, prepare_price ) VALUES( ?, ?, ? )', 
            [ 99, 'all good', 1200.50]);

        $this->object->query_prepared('SELECT id, prepare_key, prepare_price FROM prepare_test WHERE id = ?', [3]);
        $query = $this->object->queryResult();
        foreach($query as $row) {
            $this->assertEquals(3, $row->id);
            $this->assertEquals('test 21', $row->prepare_key);
            $this->assertEquals(44.01, $row->prepare_price);
        }

        $this->object->query_prepared('SELECT id, prepare_key, prepare_price FROM prepare_test WHERE id = ?', [9]);
        $query = $this->object->queryResult();
        foreach($query as $row) {
            $this->assertEquals(9, $row->id);
            $this->assertEquals('test 1', $row->prepare_key);
            $this->assertEquals(7.12, $row->prepare_price);
        }

        $this->object->query_prepared('SELECT id, prepare_key, prepare_price FROM prepare_test WHERE id = ?', [99]);
        $query = $this->object->queryResult();
        foreach($query as $row) {
            $this->assertEquals(99, $row->id);
            $this->assertEquals('all good', $row->prepare_key);
            $this->assertEquals(1200.50, $row->prepare_price);
        }

        $this->object->drop('prepare_test');   
    } // testQuery_prepared
       
    /**
     * @covers ezsql\Database\ez_mysqli::__construct
     */
    public function test__construct_Error() 
    {
        $this->expectExceptionMessageRegExp('/[Missing configuration details]/');
        $this->assertNull(new ez_mysqli());
    } 

    /**
     * @covers ezsql\Database\ez_mysqli::__construct
     */
    public function test__construct() 
    {
        unset($GLOBALS['ez'.\MYSQLI]);
        $settings = new Config('mysqli', [self::TEST_DB_USER, self::TEST_DB_PASSWORD, self::TEST_DB_NAME]);
        $this->assertNotNull(new ez_mysqli($settings));
    } 
} // ez_mysqliTest