<?php

namespace presentkim\geometryapi;

use pocketmine\plugin\PluginBase;

use presentkim\geometryapi\command\PoolCommand;
use presentkim\geometryapi\command\subcommands\{
  ListSubCommand, LangSubCommand, ReloadSubCommand, SaveSubCommand
};
use presentkim\geometryapi\listener\PlayerEventListener;
use presentkim\geometryapi\util\Translation;

class GeometryAPI extends PluginBase{

    /** @var self */
    private static $instance = null;

    /** @var string */
    public static $prefix = '';

    /** @return self */
    public static function getInstance() : self{
        return self::$instance;
    }

    /** @var PoolCommand */
    private $command;

    /** @var string[] */
    private $geometryDatas = [];

    public function onLoad() : void{
        if (self::$instance === null) {
            self::$instance = $this;
            $this->getServer()->getLoader()->loadClass('presentkim\geometryapi\util\Utils');
            Translation::loadFromResource($this->getResource('lang/eng.yml'), true);
        }
    }

    public function onEnable() : void{
        $this->load();
        $this->getServer()->getPluginManager()->registerEvents(new PlayerEventListener(), $this);
    }

    public function onDisable() : void{
        $this->save();
    }

    public function load() : void{
        $dataFolder = $this->getDataFolder();
        if (!file_exists($dataFolder)) {
            mkdir($dataFolder, 0777, true);
        }
        if (!file_exists($jsonFolder = "{$dataFolder}json/")) {
            mkdir($jsonFolder, 0777, true);
        }

        $this->geometryDatas = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($jsonFolder)) as $path => $fileInfo) {
            if (!is_dir($path) && strcasecmp(substr($path, -5), '.json') === 0) {
                $this->geometryDatas[substr($path, 0, strlen($path) - 5)] = file_get_contents($path);
            }
        }

        $langfilename = $dataFolder . 'lang.yml';
        if (!file_exists($langfilename)) {
            $resource = $this->getResource('lang/eng.yml');
            fwrite($fp = fopen("{$dataFolder}lang.yml", "wb"), $contents = stream_get_contents($resource));
            fclose($fp);
            Translation::loadFromContents($contents);
        } else {
            Translation::load($langfilename);
        }

        self::$prefix = Translation::translate('prefix');
        $this->reloadCommand();
    }

    public function save() : void{
        $dataFolder = $this->getDataFolder();
        if (!file_exists($dataFolder)) {
            mkdir($dataFolder, 0777, true);
        }
        if (!file_exists($jsonFolder = "{$dataFolder}json/")) {
            mkdir($jsonFolder, 0777, true);
        }

        foreach ($this->geometryDatas as $geometryName => $geometryData) {
            file_put_contents("{$jsonFolder}{$geometryName}.json", $geometryData);
        }
        $this->saveConfig();
    }

    public function reloadCommand() : void{
        if ($this->command == null) {
            $this->command = new PoolCommand($this, 'geometry');
            $this->command->createSubCommand(ListSubCommand::class);
            $this->command->createSubCommand(LangSubCommand::class);
            $this->command->createSubCommand(ReloadSubCommand::class);
            $this->command->createSubCommand(SaveSubCommand::class);
        }
        $this->command->updateTranslation();
        $this->command->updateSudCommandTranslation();
        if ($this->command->isRegistered()) {
            $this->getServer()->getCommandMap()->unregister($this->command);
        }
        $this->getServer()->getCommandMap()->register(strtolower($this->getName()), $this->command);
    }

    /**  @return string[] */
    public function getGeometryDatas() : array{
        return $this->geometryDatas;
    }

    /**
     * @param string $geometryName
     * @param string $geometryData
     */
    public function addGeometryData(string $geometryName, string $geometryData){
        if (!isset($this->geometryDatas[$geometryName])) {
            $this->geometryDatas[$geometryName] = $geometryData;
        }
    }
}
