<?php

namespace TestsDb\AnyDataset;

use ByJG\AnyDataset\Db\DbPdoDriver;
use ByJG\AnyDataset\Db\Factory;
use ByJG\Util\Uri;

require_once 'BasePdo.php';

class PdoSqliteTest extends BasePdo
{
    protected $host;

    protected function createInstance()
    {
        $this->host = getenv('SQLITE_TEST_HOST');
        if (empty($host)) {
            $this->host = "/tmp/test.db";
        }

        $uri = Uri::getInstanceFromString("sqlite://" . $this->host)
            ->withQueryKeyValue(DbPdoDriver::STATEMENT_CACHE, "true");

        $this->dbDriver = Factory::getDbInstance($uri);
    }

    protected function createDatabase()
    {
        //create the database
        $this->dbDriver->execute("CREATE TABLE Dogs (Id INTEGER PRIMARY KEY, Breed VARCHAR(50), Name VARCHAR(50), Age INTEGER)");
    }

    public function deleteDatabase()
    {
        unlink($this->host);
    }

    public function testGetAllFields()
    {
        $this->markTestSkipped('Skipped: SqlLite does not support get all fields');
    }


    public function testStatementCache()
    {
        $this->assertEquals("true", $this->dbDriver->getUri()->getQueryPart(DbPdoDriver::STATEMENT_CACHE));
        $this->assertEquals(2, $this->dbDriver->getCountStmtCache()); // because of createDatabase() and populateData()

        $i = 3;
        while ($i<=10) {
            $it = $this->dbDriver->getIterator("select $i as name");
            $this->assertEquals($i, $this->dbDriver->getCountStmtCache()); // because of createDatabase() and populateData()
            $this->assertEquals([["name" => $i]], $it->toArray());
            $i++;
        }

        $it = $this->dbDriver->getIterator("select 20 as name");
        $this->assertEquals(10, $this->dbDriver->getCountStmtCache()); // because of createDatabase() and populateData()
        $this->assertEquals([["name" => 20]], $it->toArray());

        $it = $this->dbDriver->getIterator("select 30 as name");
        $this->assertEquals(10, $this->dbDriver->getCountStmtCache()); // because of createDatabase() and populateData()
        $this->assertEquals([["name" => 30]], $it->toArray());
    }

    public function testGetDate() {
        $data = $this->dbDriver->getScalar("SELECT DATE('2018-07-26') ");
        $this->assertEquals("2018-07-26", $data);

        $data = $this->dbDriver->getScalar("SELECT DATETIME('2018-07-26 20:02:03') ");
        $this->assertEquals("2018-07-26 20:02:03", $data);
    }

    public function testDontBindParam()
    {
        try {
            parent::testDontBindParam();
            $this->fail();
        } catch (\PDOException $ex) {
            if (strpos($ex->getMessage(), "SQLSTATE[08P01]") === false) {
                throw $ex;
            }
            $this->assertTrue(true);
        }
    }

}
