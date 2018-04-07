<?php
require_once('ez_sql_loader.php');

require 'vendor/autoload.php';
use PHPUnit\Framework\TestCase;

/**
 * Test class for ezSQL_mysqli.
 * Generated by PHPUnit
 *
 * Needs database tear up to run test, that creates database and a user with
 * appropriate rights.
 * Run database tear down after tests to get rid of the database and the user.
 *
 * @author  Stefanie Janine Stoelting <mail@stefanie-stoelting.de>
 * @Contributor  Lawrence Stubbs <technoexpressnet@gmail.com>
 * @name    ezSQL_mysqliTest
 * @package ezSQL
 * @subpackage Tests
 * @license FREE / Donation (LGPL - You may do what you like with ezSQL - no exceptions.)
 */
class ezSQL_mysqliTest extends TestCase {

    /**
     * constant string user name
     */
    const TEST_DB_USER = 'ez_test';

    /**
     * constant string password
     */
    const TEST_DB_PASSWORD = 'ezTest';

    /**
     * constant database name
     */
    const TEST_DB_NAME = 'ez_test';

    /**
     * constant database host
     */
    const TEST_DB_HOST = 'localhost';

    /**
     * constant database connection charset
     */
    const TEST_DB_CHARSET = 'utf8';

    /**
     * @var ezSQL_mysqli
     */
    protected $object;
    private $errors;
 
    function errorHandler($errno, $errstr, $errfile, $errline, $errcontext) {
        $this->errors[] = compact("errno", "errstr", "errfile",
            "errline", "errcontext");
    }

    function assertError($errstr, $errno) {
        foreach ($this->errors as $error) {
            if ($error["errstr"] === $errstr
                && $error["errno"] === $errno) {
                return;
            }
        }
        $this->fail("Error with level " . $errno .
            " and message '" . $errstr . "' not found in ", 
            var_export($this->errors, TRUE));
    }   
    
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        if (!extension_loaded('mysqli')) {
            $this->markTestSkipped(
              'The MySQLi extension is not available.'
            );
        }
        $this->object = new ezSQL_mysqli();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        if ($this->object->isConnected()) {
            $this->object->select(self::TEST_DB_NAME);
            $this->assertEquals($this->object->query('DROP TABLE IF EXISTS unit_test'), 0);
        }
        $this->object = null;
    }
       
    /**
     * @covers ezSQL_mysqli::quick_connect
     */
    public function testQuick_connect() {
        $result = $this->object->quick_connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD, self::TEST_DB_NAME);

        $this->assertTrue($result);
    }

    /**
     * @covers ezSQL_mysqli::quick_connect
     */
    public function testQuick_connect2() {
        $result = $this->object->quick_connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD, self::TEST_DB_NAME, self::TEST_DB_CHARSET);

        $this->assertTrue($result);
    }

    /**
     * @covers ezSQL_mysqli::connect
     */
    public function testConnect() {        
        $this->errors = array();
        set_error_handler(array($this, 'errorHandler')); 
         
        $this->assertFalse($this->object->connect('',''));  
        $this->assertFalse($this->object->connect('self::TEST_DB_USER', 'self::TEST_DB_PASSWORD',' self::TEST_DB_NAME', 'self::TEST_DB_CHARSET'));  
        $result = $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);

        $this->assertTrue($result);
    } // testConnect

    /**
     * @covers ezSQL_mysqli::select
     */
    public function testSelect() {
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);
        $this->assertTrue($this->object->isConnected());

        $result = $this->object->select(self::TEST_DB_NAME);

        $this->assertTrue($result);

        $this->errors = array();
        set_error_handler(array($this, 'errorHandler')); 
        $this->assertFalse($this->object->select(''));
        $this->object->disconnect();
        $this->assertFalse($this->object->select('notest'));
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);
        $this->assertFalse($this->object->select('notest'));
        $this->assertTrue($this->object->select(self::TEST_DB_NAME));        
    } // testSelect

    /**
     * @covers ezSQL_mysqli::escape
     */
    public function testEscape() {
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);
        $result = $this->object->escape("This is'nt escaped.");

        $this->assertEquals("This is\\'nt escaped.", $result);
    } // testEscape

    /**
     * @covers ezSQL_mysqli::sysdate
     */
    public function testSysdate() {
        $this->assertEquals('NOW()', $this->object->sysdate());
    } // testSysdate

    /**
     * @covers ezSQL_mysqli::query
     */
    public function testQueryInsert() {
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);

        $this->object->select(self::TEST_DB_NAME);

        $this->assertEquals($this->object->query('CREATE TABLE unit_test(id integer, test_key varchar(50), PRIMARY KEY (ID))'), 0);
        $this->assertEquals($this->object->query('INSERT INTO unit_test(id, test_key) VALUES(1, \'test 1\')'), 1);
        
        $this->object->dbh = null;
        $this->assertEquals($this->object->query('INSERT INTO unit_test(id, test_key) VALUES(2, \'test 2\')'),1);
        $this->object->disconnect();
        $this->assertNull($this->object->query('INSERT INTO unit_test(id, test_key) VALUES(3, \'test 3\')'));        
    } // testQueryInsert

    /**
     * @covers ezSQL_mysqli::query
     */
    public function testQuerySelect() {
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
    } // testQuerySelect

    /**
     * @covers ezSQL_mysqli::getDBHost
     */
    public function testGetDBHost() {
        $this->assertEquals(self::TEST_DB_HOST, $this->object->getDBHost());
    } // testGetDBHost

    /**
     * @covers ezSQL_mysqli::getCharset
     */
    public function testGetCharset() {
        $this->assertEquals(self::TEST_DB_CHARSET, $this->object->getCharset());
    } // testGetCharset
    
    /**
     * @covers ezSQLcore::get_set
     */
    public function testGet_set()
    {
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);
        $this->object->select(self::TEST_DB_NAME);
        $this->assertContains('NOW()',$this->object->get_set(
            array('test_unit'=>'NULL',
            'test_unit2'=>'NOW()',
            'test_unit3'=>'true',
            'test_unit4'=>'false')));   
    }

    /**
     * @covers ezSQL_mysqli::disconnect
     */
    public function testDisconnect() {
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);
        $this->object->select(self::TEST_DB_NAME);
        $this->object->disconnect();
        $this->assertFalse($this->object->isConnected());
    } // testDisconnect

    /**
     * @covers ezSQL_mysqli::getInsertId
     */
    public function testGetInsertId() {
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);

        $this->object->select(self::TEST_DB_NAME);

        $this->assertEquals($this->object->query('CREATE TABLE unit_test(id int(11) NOT NULL AUTO_INCREMENT, test_key varchar(50), PRIMARY KEY (ID))ENGINE=MyISAM  DEFAULT CHARSET=utf8'), 0);
        $this->assertEquals($this->object->query('INSERT INTO unit_test(id, test_key) VALUES(1, \'test 1\')'), 1);

        $this->assertEquals(1, $this->object->getInsertId($this->object->dbh));
    } // testInsertId
    
    /**
     * @covers ezQuery::insert
     */
    public function testInsert()
    {
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);
        $this->object->select(self::TEST_DB_NAME);
            $this->assertEquals($this->object->query('DROP TABLE IF EXISTS unit_test'), 0);
        $this->object->query('CREATE TABLE unit_test(id int(11) NOT NULL AUTO_INCREMENT, test_key varchar(50), PRIMARY KEY (ID))ENGINE=MyISAM  DEFAULT CHARSET=utf8');
        $this->assertEquals($this->object->insert('unit_test', array('id'=>2, 'test_key'=>'test 2' )), 2);
    }
        
    /**
     * @covers ezQuery::replace
     */
    public function testReplace()
    {
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);
        $this->object->select(self::TEST_DB_NAME);
            $this->assertEquals($this->object->query('DROP TABLE IF EXISTS unit_test'), 0);
        $this->object->query('CREATE TABLE unit_test(id int(11) NOT NULL AUTO_INCREMENT, test_key varchar(50), PRIMARY KEY (ID))ENGINE=MyISAM  DEFAULT CHARSET=utf8');
        $this->object->insert('unit_test', array('id'=>'2', 'test_key'=>'test 2' ));
		$this->object->hasprepare = false;
        $this->assertEquals($this->object->replace('unit_test', array('id'=>'2', 'test_key'=>'test 3' )), 2);
    }
    
    /**
     * @covers ezQuery::update
     */
    public function testUpdate()
    {
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);
        $this->object->select(self::TEST_DB_NAME);  
		$this->object->hasprepare = false;
        $this->object->query('CREATE TABLE unit_test(id int(11) NOT NULL AUTO_INCREMENT, test_key varchar(50), PRIMARY KEY (ID))ENGINE=MyISAM  DEFAULT CHARSET=utf8');
        $this->object->insert('unit_test', array('id'=>1, 'test_key'=>'test 1' ));
        $this->object->insert('unit_test', array('id'=>2, 'test_key'=>'test 2' ));
        $this->object->insert('unit_test', array('id'=>3, 'test_key'=>'test 3' ));
        $unit_test['test_key'] = 'testing';
        $where="id  =  1";
        $this->assertNotFalse($this->object->update('unit_test', $unit_test, $where));
        $this->assertEquals($this->object->update('unit_test', $unit_test, 
			array('test_key',EQ,'test 3','and'),
			array('id','=',3)), 1);
        $this->assertEquals($this->object->update('unit_test', $unit_test, "id = 4"), 0);
        $this->assertEquals($this->object->update('unit_test', $unit_test, "test_key  =  test 2  and", "id  =  2"), 1);
		$this->object->hasprepare = true;
    }
    
    /**
     * @covers ezQuery::delete
     */
    public function testDelete()
    {
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);
        $this->object->select(self::TEST_DB_NAME);
		$this->object->hasprepare = false;
        $this->object->query('CREATE TABLE unit_test(id int(11) NOT NULL AUTO_INCREMENT, test_key varchar(50), PRIMARY KEY (ID))ENGINE=MyISAM  DEFAULT CHARSET=utf8');
        $unit_test['id'] = '1';
        $unit_test['test_key'] = 'test 1';
        $this->object->insert('unit_test', $unit_test );
        $unit_test['id'] = '2';
        $unit_test['test_key'] = 'test 2';
        $this->object->insert('unit_test', $unit_test );
        $unit_test['id'] = '3';
        $unit_test['test_key'] = 'test 3';
        $this->object->insert('unit_test', $unit_test );
        $where='1';
        $this->assertEquals($this->object->delete('unit_test', array('id','=','1')), 1);
        $this->assertEquals($this->object->delete('unit_test', 
            array('test_key','=',$unit_test['test_key'],'and'),
            array('id','=','3')), 1);
        $this->assertEquals($this->object->delete('unit_test', array('test_key','=',$where)), 0);
        $where="id  =  2";
        $this->assertEquals($this->object->delete('unit_test', $where), 1);
		$this->object->hasprepare = true;
    }  
       
    /**
     * @covers ezQuery::selecting
     */
    public function testSelecting()
    {
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);
        $this->object->select(self::TEST_DB_NAME);
        $this->object->query('CREATE TABLE unit_test(id int(11) NOT NULL AUTO_INCREMENT, test_key varchar(50), PRIMARY KEY (ID))ENGINE=MyISAM  DEFAULT CHARSET=utf8');
        $this->object->insert('unit_test', array('id'=>'1', 'test_key'=>'testing 1' ));
        $this->object->insert('unit_test', array('id'=>'2', 'test_key'=>'testing 2' ));
        $this->object->insert('unit_test', array('id'=>'3', 'test_key'=>'testing 3' ));
        
        $result = $this->object->selecting('unit_test');
        $i = 1;
        foreach ($result as $row) {
            $this->assertEquals($i, $row->id);
            $this->assertEquals('testing ' . $i, $row->test_key);
            ++$i;
        }
        
        $where=array('test_key','=','testing 2');
        $result = $this->object->selecting('unit_test', 'id', $where);
        foreach ($result as $row) {
            $this->assertEquals(2, $row->id);
        }
        
        $result = $this->object->selecting('unit_test', 'test_key', array( 'id','=','3' ));
        foreach ($result as $row) {
            $this->assertEquals('testing 3', $row->test_key);
        }
        
        $result = $this->object->selecting('unit_test', array ('test_key'), "id  =  1");
        foreach ($result as $row) {
            $this->assertEquals('testing 1', $row->test_key);
        }
    }    
          
    /**
     * @covers ezQuery::create_select
     */
    public function testCreate_select()
    {
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);
        $this->object->select(self::TEST_DB_NAME);
        $this->object->query('CREATE TABLE unit_test(id int(11) NOT NULL AUTO_INCREMENT, test_key varchar(50), PRIMARY KEY (ID))ENGINE=MyISAM  DEFAULT CHARSET=utf8');
        $this->object->insert('unit_test', array('id'=>'1', 'test_key'=>'testing 1' ));
        $this->object->insert('unit_test', array('id'=>'2', 'test_key'=>'testing 2' ));
        $this->object->insert('unit_test', array('id'=>'3', 'test_key'=>'testing 3' ));
        
		$this->assertEquals($this->object->create_select('new_new_test','*','unit_test'),0);
		$result = $this->object->selecting('new_new_test');
        $i = 1;
        foreach ($result as $row) {
            $this->assertEquals($i, $row->id);
            $this->assertEquals('testing ' . $i, $row->test_key);
            ++$i;
        }
        $this->assertEquals($this->object->query('DROP TABLE IF EXISTS new_new_test'), 0);    
    }    
              
    /**
     * @covers ezQuery::insert_select
     */
    public function testInsert_select()
    {
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);
        $this->object->select(self::TEST_DB_NAME);
		$this->object->hasprepare = false;
        $this->object->query('CREATE TABLE unit_test(id int(11) NOT NULL AUTO_INCREMENT, test_key varchar(50), PRIMARY KEY (ID))ENGINE=MyISAM  DEFAULT CHARSET=utf8');
        $this->object->insert('unit_test', array('id'=>'1', 'test_key'=>'testing 1' ));
        $this->object->insert('unit_test', array('id'=>'2', 'test_key'=>'testing 2' ));
        $this->object->insert('unit_test', array('id'=>'3', 'test_key'=>'testing 3' ));
        
        $this->assertEquals($this->object->query('DROP TABLE IF EXISTS new_select_test'), 0);
        $this->object->query('CREATE TABLE new_select_test(id int(11) NOT NULL AUTO_INCREMENT, test_key varchar(50), PRIMARY KEY (ID))ENGINE=MyISAM  DEFAULT CHARSET=utf8');
		
		$this->assertEquals($this->object->insert_select('new_select_test','*','unit_test'),3);
        setQuery('mySQLi');
		$result = select('new_select_test');
        $i = 1;
        foreach ($result as $row) {
            $this->assertEquals($i, $row->id);
            $this->assertEquals('testing ' . $i, $row->test_key);
            ++$i;
        }
        $this->assertEquals($this->object->query('DROP TABLE IF EXISTS new_select_test'), 0);
    }    
	
    /**
     * @covers ezQuery::where
     */
    public function testWhere()
    {
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);
        $this->object->select(self::TEST_DB_NAME);
		$this->object->hasprepare = false;
        setQuery('mySQLi');
        $expect = where(
            between('where_test','testing 1','testing 2','bad'),
			like('test_null','null')
			);

        $this->assertContains('WHERE where_test BETWEEN \'testing 1\' AND \'testing 2\' AND test_null IS NULL',$expect);
        $this->assertFalse(where(
            array('where_test','bad','testing 1','or'),
			array('test_null','like','null')
			));
        $expect = $this->object->where(
            array('where_test',_IN,'testing 1','testing 2','testing 3','testing 4','testing 5')
			);
        $this->assertContains('WHERE',$expect);
        $this->assertContains('IN',$expect);
        $this->assertContains('(',$expect);
        $this->assertContains('testing 1',$expect);
        $this->assertContains('testing 4\',',$expect);
        $this->assertContains(')',$expect);
        $expect = $this->object->where("where_test  in  testing 1  testing 2  testing 3  testing 4  testing 5");
        $this->assertContains('WHERE',$expect);
        $this->assertContains('IN',$expect);
        $this->assertContains('(',$expect);
        $this->assertContains('testing 2\'',$expect);
        $this->assertContains('testing 5',$expect);
        $this->assertContains(')',$expect);
        $this->assertFalse($this->object->where(
            array('where_test','=','testing 1','or'),
			array('test_like','LIKE',':bad')
			));
        $this->assertContains('_good',$this->object->where(
            array('where_test','=','testing 1','or'),
			array('test_like',_LIKE,'_good')
			));
		$this->object->hasprepare = true;
    } 
    
    /**
     * @covers ezQuery::_query_insert_replace
     */
    public function test_Query_insert_replace() 
    {
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);
        $this->object->select(self::TEST_DB_NAME);
        $this->object->query('CREATE TABLE unit_test(id int(11) NOT NULL AUTO_INCREMENT, test_key varchar(50), PRIMARY KEY (ID))ENGINE=MyISAM  DEFAULT CHARSET=utf8');
        setQuery('mySQLi');
        $this->assertEquals(insert('unit_test', array('id'=>'2', 'test_key'=>'test 2' )), 2); 
    } 
    
    /**
     * @covers ezSQL_mysqli::prepare
     */
    public function testPrepare() {
        $this->object->connect(self::TEST_DB_USER, self::TEST_DB_PASSWORD);

        $this->object->select(self::TEST_DB_NAME);

        $parameter = '\'test 1\'';

        $this->assertEquals($this->object->query('CREATE TABLE unit_test(id int(11) NOT NULL AUTO_INCREMENT, test_key varchar(50), PRIMARY KEY (ID))ENGINE=MyISAM  DEFAULT CHARSET=utf8'), 0);
        $result = $this->object->prepare('INSERT INTO unit_test(id, test_key) VALUES(1, ?)');
        $this->assertInstanceOf('mysqli_stmt', $result);
        $result->bind_param('s', $parameter);

        $this->assertTrue($result->execute());
    } // testPrepare
       
    /**
     * @covers ezSQL_mysqli::__construct
     */
    public function test__Construct() {         
        $mysqli = $this->getMockBuilder(ezSQL_mysqli::class)
        ->setMethods(null)
        ->disableOriginalConstructor()
        ->getMock();
        
        $this->assertNull($mysqli->__construct());  
        $this->assertNull($mysqli->__construct('testuser','','','','utf8'));  
    } 
} // ezSQL_mysqliTest