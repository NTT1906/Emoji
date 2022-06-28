<?php
declare(strict_types = 1);

namespace skymin\emoji;

use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\utils\AssumptionFailedError;

use skymin\data\Data;
use skymin\emoji\command\EmojiCmd;
use skymin\emoji\exception\EmojiResourcePackException;

final class Loader extends PluginBase{
	private static Loader $instance;

	private const uuid = 'eef1262f-003b-41bd-94f0-b0b61e34b1f6';
	private const version = '1.0.0';
	private const pack_name = "EmojiParticle.mcpack";

	private Emoji $emoji;

	/**
	 * @throws \ReflectionException
	 */
	public function onLoad() : void{
		self::$instance = $this;
		$this->saveResource("emoji.json");

		$rp_path = $this->getServer()->getDataPath() . "resource_packs/";

		// Copy the pack to the resource packs folder
		if (!file_exists($rp_path)) {
			$resource = $this->getResource(self::pack_name);
			if ($resource !== null) {
				$fp = fopen($rp_path . self::pack_name, "wb");
				if($fp === false) {
					throw new AssumptionFailedError("fopen() should not fail with wb flags");
				}
				stream_copy_to_stream($resource, $fp);
				fclose($fp);
				fclose($resource);
			}
		}

		$resourcepackManager = $this->getServer()->getResourcePackManager();
		/*
		$pack = new ZippedResourcePack($rp_path);
		$uuid = $pack->getPackId();
		$version = $pack->getPackVersion();
		 */
		$pack = $resourcepackManager->getPackById(self::uuid);
		if ($pack === null) {
			$pack = new ZippedResourcePack($rp_path . self::pack_name);
			$reflection = new \ReflectionClass($resourcepackManager);
			$property = $reflection->getProperty("resourcePacks");
			/** @var mixed $property */
			$property->setAccessible(true);
			$currentResourcePacks = $property->getValue($resourcepackManager);
			$currentResourcePacks[] = $pack;
			$property->setValue($resourcepackManager, $currentResourcePacks);


			$property = $reflection->getProperty("uuidList");
			$property->setAccessible(true);
			$currentUUIDPacks = $property->getValue($resourcepackManager);
			$currentUUIDPacks[strtolower($pack->getPackId())] = $pack;
			$property->setValue($resourcepackManager, $currentUUIDPacks);

			$data = new Data($rp_path . "resource_packs.yml"); //Add the pack to the resourcepack stack
			$rpstack = $data->__get("resource_stack");
			$rpstack[] = basename(self::pack_name);
			$data->__set("resource_stack", $rpstack);
			$data->save();
		}

		if (!$resourcepackManager->resourcePacksRequired()) {
			$reflection = new \ReflectionClass($resourcepackManager);
			$property = $reflection->getProperty("serverForceResources");
			/** @var mixed $property */
			$property->setAccessible(true);
			$property->setValue($resourcepackManager, true);
		}
		if ($pack->getPackVersion() !== self::version) {
			throw new EmojiResourcePackException("'Emoji Particle Resource Pack' version did not match");
		}
	}

	public static function getInstance() : self{
		return self::$instance;
	}

	protected function onEnable() : void{
		$this->saveResource("emoji.json");
		$this->emoji = new Emoji((new Data($this->getDataFolder() . "emoji.json"))->getAll());
		$this->getServer()->getCommandMap()->register('emoji', new EmojiCmd($this));
	}

	public function getEmoji() : Emoji{
		return $this->emoji;
	}
}