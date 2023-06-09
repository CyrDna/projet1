<?php
// Copyright (c) 2010-2017 Combodo SARL
//
//   This file is part of iTop.
//
//   iTop is free software; you can redistribute it and/or modify
//   it under the terms of the GNU Affero General Public License as published by
//   the Free Software Foundation, either version 3 of the License, or
//   (at your option) any later version.
//
//   iTop is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU Affero General Public License for more details.
//
//   You should have received a copy of the GNU Affero General Public License
//   along with iTop. If not, see <http://www.gnu.org/licenses/>
//

/**
 * Created by PhpStorm.
 * User: Eric
 * Date: 02/10/2017
 * Time: 13:58
 */

namespace Combodo\iTop\Test\UnitTest\Core;

use Combodo\iTop\Test\UnitTest\ItopDataTestCase;
use DBObject;
use MetaModel;


/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @backupGlobals disabled
 */
class DBObjectTest extends ItopDataTestCase
{
	const CREATE_TEST_ORG = true;

	protected function setUp(): void
	{
		parent::setUp();
		$this->RequireOnceItopFile('core/coreexception.class.inc.php');
		$this->RequireOnceItopFile('core/dbobject.class.php');
	}

	/**
	 * Test default page name
	 */
	public function testGetUIPage()
	{
		static::assertEquals('UI.php', DBObject::GetUIPage());
	}

	/**
	 * Test PKey validation
	 * @dataProvider keyProviderOK
	 * @param $key
	 * @param $res
	 */
	public function testIsValidPKeyOK($key, $res)
	{
		static::assertEquals(DBObject::IsValidPKey($key), $res);
	}

	public function keyProviderOK()
	{
		return array(
			array(1, true),
			array('255', true),
			array(-24576, true),
			array(0123, true),
			array(0xCAFE, true),
			array(PHP_INT_MIN, true),
			array(PHP_INT_MAX, true),
			array('test', false),
			array('', false),
			array('a255', false),
			array('PHP_INT_MIN', false));
	}

	public function testGetOriginal()
	{
		$oObject = $this->CreateUserRequest(190664);

		static::assertNull($oObject->GetOriginal('sla_tto_passed'));
	}

	/**
	 * @return void
	 * @throws \Exception
	 */
	public function testListPreviousValuesForUpdatedAttributes()
	{
		$oOrg = $this->CreateOrganization('testListPreviousValuesForUpdatedAttributes');

		$this->assertCount(0, $oOrg->ListChanges());
		$oOrg->Set('code', strtoupper('testListPreviousValuesForUpdatedAttributes'));
		$this->assertCount(1, $oOrg->ListChanges());
		$oOrg->DBUpdate();
		$this->assertCount(0, $oOrg->ListChanges());
		$this->assertCount(1, $oOrg->ListPreviousValuesForUpdatedAttributes());

		$oOrg->DBUpdate();

		$this->assertCount(0, $oOrg->ListChanges());
		$this->assertCount(1, $oOrg->ListPreviousValuesForUpdatedAttributes());

		$oOrg->DBDelete();

		$oOrg = MetaModel::NewObject('Organization');
		$oOrg->Set('name', 'testListPreviousValuesForUpdatedAttributes');
		$oOrg->DBInsert();
		$oOrg->Set('code', strtoupper('testListPreviousValuesForUpdatedAttributes'));
		$oOrg->DBUpdate();
		$oOrg->DBUpdate();
		$this->markTestIncomplete('This test has not been implemented yet. wait for N°4967 fix');
		$this->debug("ERROR: N°4967 - 'Previous Values For Updated Attributes' not updated if DBUpdate is called without modifying the object");
		//$this->assertCount(0, $oOrg->ListPreviousValuesForUpdatedAttributes());
	}
}
