<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vlastimil Pecinka <pecinka@email.cz>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Files\Mount;

use OCP\IConfig;
use OCP\ILogger;
use OCP\IUser;

/**
 * Base mount provider for object store storages
 */
abstract class ObjectStoreBaseProvider {
	/**
	 * @var IConfig
	 */
	protected $config;

	/**
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * @param IUser $user
	 * @return array|null
	 */
	protected function getSingleBucketObjectStoreConfig(IUser $user) {
		$config = $this->config->getSystemValue('objectstore');
		if (!is_array($config)) {
			return null;
		}

		// sanity checks
		if (empty($config['class'])) {
			\OCP\Util::writeLog('files', 'No class given for objectstore', ILogger::ERROR);
		}
		if (!isset($config['arguments'])) {
			$config['arguments'] = [];
		}
		// instantiate object store implementation
		$config['arguments']['objectstore'] = new $config['class']($config['arguments']);

		$config['arguments']['user'] = $user;

		return $config;
	}

	/**
	 * @param IUser $user
	 * @return array|null
	 */
	protected function getMultiBucketObjectStoreConfig(IUser $user) {
		$config = $this->config->getSystemValue('objectstore_multibucket');
		if (!is_array($config)) {
			return null;
		}

		// sanity checks
		if (empty($config['class'])) {
			\OCP\Util::writeLog('files', 'No class given for objectstore', ILogger::ERROR);
		}
		if (!isset($config['arguments'])) {
			$config['arguments'] = [];
		}

		$bucket = $this->config->getUserValue($user->getUID(), 'homeobjectstore', 'bucket', null);

		if ($bucket === null) {
			/*
			 * Use any provided bucket argument as prefix
			 * and add the mapping from username => bucket
			 */
			if (!isset($config['arguments']['bucket'])) {
				$config['arguments']['bucket'] = '';
			}
			$mapper = new \OC\Files\ObjectStore\Mapper($user);
			$numBuckets = isset($config['arguments']['num_buckets']) ? $config['arguments']['num_buckets'] : 64;
			$config['arguments']['bucket'] .= $mapper->getBucket($numBuckets);

			$this->config->setUserValue($user->getUID(), 'homeobjectstore', 'bucket', $config['arguments']['bucket']);
		} else {
			$config['arguments']['bucket'] = $bucket;
		}

		// instantiate object store implementation
		$config['arguments']['objectstore'] = new $config['class']($config['arguments']);

		$config['arguments']['user'] = $user;

		return $config;
	}
}
