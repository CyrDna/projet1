<?php
/**
 * Copyright (C) 2013-2019 Combodo SARL
 *
 * This file is part of iTop.
 *
 * iTop is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * iTop is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 */

namespace Combodo\iTop\Test\UnitTest;
/**
 * Created by PhpStorm.
 * User: Eric
 * Date: 20/11/2017
 * Time: 11:21
 */

use CMDBSource;
use MySQLTransactionNotClosedException;
use PHPUnit\Framework\TestCase;
use SetupUtils;

define('DEBUG_UNIT_TEST', true);

class ItopTestCase extends TestCase
{
	const TEST_LOG_DIR = 'test';

	/** @noinspection UsingInclusionOnceReturnValueInspection avoid errors for approot includes */
	protected function setUp(): void
	{
		$sAppRootRelPath = 'approot.inc.php';
		$sDepthSeparator = '../';
		for ($iDepth = 0; $iDepth < 8; $iDepth++) {
			if (file_exists($sAppRootRelPath)) {
				require_once $sAppRootRelPath;
				break;
			}

			$sAppRootRelPath = $sDepthSeparator.$sAppRootRelPath;
		}
	}

	/**
	 * @throws \MySQLTransactionNotClosedException see N°5538
	 * @since 2.7.8 3.0.3 3.1.0 N°5538
	 */
	protected function tearDown(): void
	{
		parent::tearDown();

		if (CMDBSource::IsInsideTransaction()) {
			// Nested transactions were opened but not finished !
			throw new MySQLTransactionNotClosedException('Some DB transactions were opened but not closed ! Fix the code by adding ROLLBACK or COMMIT statements !', []);
		}
	}

	/**
	 * Require once an iTop file (core or extension) from its relative path to the iTop root dir.
	 * This ensure to always use the right absolute path, especially in {@see \Combodo\iTop\Test\UnitTest\ItopTestCase::RequireOnceUnitTestFile()}
	 *
	 * @param string $sFileRelPath Rel. path (from iTop root dir) of the iTop file (core or extension) to require (eg. 'core/attributedef.class.inc.php' for <ITOP>/core/attributedef.class.inc.php)
	 *
	 * @return void
	 * @since 2.7.9 3.0.3 3.1.0 N°5608 Add method after PHPUnit directory moving
	 */
	protected function RequireOnceItopFile(string $sFileRelPath): void
	{
		require_once APPROOT . $sFileRelPath;
	}

	/**
	 * Require once a unit test file (eg. a mock class) from its relative path from the *current* dir.
	 * This ensure that required files don't crash when unit tests dir is moved in the iTop structure (see N°5608)
	 *
	 * @param string $sFileRelPath Rel. path (from the *current* dir) of the unit test file to require (eg. './WeeklyScheduledProcessMockConfig.php' for <ITOP>/tests/php-unit-tests/unitary-tests/core/WeeklyScheduledProcessMockConfig.php in Combodo\iTop\Test\UnitTest\Core\WeeklyScheduledProcessTest)
	 *
	 * @return void
	 * @since 2.7.9 3.0.3 3.1.0 N°5608 Add method after PHPUnit directory moving
	 */
	protected function RequireOnceUnitTestFile(string $sFileRelPath): void
	{
		$aStack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
		$sCallerDirAbsPath = dirname($aStack[0]['file']);

		require_once $sCallerDirAbsPath . DIRECTORY_SEPARATOR . $sFileRelPath;
	}

	protected function debug($sMsg)
	{
		if (DEBUG_UNIT_TEST) {
			if (is_string($sMsg)) {
				echo "$sMsg\n";
			} else {
		        /** @noinspection ForgottenDebugOutputInspection */
		        print_r($sMsg);
	        }
        }
    }

	public function GetMicroTime()
	{
		list($uSec, $sec) = explode(" ", microtime());
		return ((float)$uSec + (float)$sec);
	}

	public function WriteToCsvHeader($sFilename, $aHeader)
	{
		$sResultFile = APPROOT.'log/'.$sFilename;
		if (is_file($sResultFile))
		{
			@unlink($sResultFile);
		}
		SetupUtils::builddir(dirname($sResultFile));
		file_put_contents($sResultFile, implode(';', $aHeader)."\n");
	}

	public function WriteToCsvData($sFilename, $aData)
	{
		$sResultFile = APPROOT.'log/'.$sFilename;
		$file = fopen($sResultFile, 'a');
		fputs($file, implode(';', $aData)."\n");
		fclose($file);
	}

	public function GetTestId()
	{
		$sId = str_replace('"', '', $this->getName());
		$sId = str_replace(' ', '_', $sId);

		return $sId;
	}

	/**
	 * @since 2.7.4 3.0.0
	 */
	public function InvokeNonPublicStaticMethod($sObjectClass, $sMethodName, $aArgs)
	{
		return $this->InvokeNonPublicMethod($sObjectClass, $sMethodName, null, $aArgs);
	}

	/**
	 * @param string $sObjectClass for example DBObject::class
	 * @param string $sMethodName
	 * @param object $oObject
	 * @param array $aArgs
	 *
	 * @return mixed method result
	 *
	 * @throws \ReflectionException
	 *
	 * @since 2.7.4 3.0.0
	 */
	public function InvokeNonPublicMethod($sObjectClass, $sMethodName, $oObject, $aArgs)
	{
		$class = new \ReflectionClass($sObjectClass);
		$method = $class->getMethod($sMethodName);
		$method->setAccessible(true);

		return $method->invokeArgs($oObject, $aArgs);
	}


	/**
	 * @param object $oObject
	 * @param string $sProperty
	 *
	 * @return mixed property
	 *
	 * @throws \ReflectionException
	 * @since 2.7.8 3.0.3 3.1.0
	 */
	public function GetNonPublicProperty(object $oObject, string $sProperty)
	{
		$class = new \ReflectionClass(get_class($oObject));
		$property = $class->getProperty($sProperty);
		$property->setAccessible(true);

		return $property->getValue($oObject);
	}

	/**
	 * @param object $oObject
	 * @param string $sProperty
	 * @param $value
	 *
	 * @throws \ReflectionException
	 * @since 2.7.8 3.0.3 3.1.0
	 */
	public function SetNonPublicProperty(object $oObject, string $sProperty, $value)
	{
		$class = new \ReflectionClass(get_class($oObject));
		$property = $class->getProperty($sProperty);
		$property->setAccessible(true);

		$property->setValue($oObject, $value);
	}
}