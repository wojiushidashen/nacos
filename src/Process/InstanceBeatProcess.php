<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Hyperf\Nacos\Process;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Nacos\Api\NacosInstance;
use Hyperf\Nacos\Contract\LoggerInterface;
use Hyperf\Nacos\Instance;
use Hyperf\Nacos\Service;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\ProcessManager;

class InstanceBeatProcess extends AbstractProcess
{
    /**
     * @var string
     */
    public $name = 'nacos-beat';

    public function handle(): void
    {
        $config = $this->container->get(ConfigInterface::class);
        $serviceConfigs = $config->get('nacos.service');
        foreach ($serviceConfigs as $type => $serviceConfig) {
            $service = make(Service::class, [$serviceConfig]);
            $instanceConfig = $config->get('nacos.client.'.$type);
            $instance = make(Instance::class, [$instanceConfig]);
            $nacosInstance = $this->container->get(NacosInstance::class);
            $logger = $this->container->get(LoggerInterface::class);
            $send = $nacosInstance->beat($service, $instance);
            if ($send) {
                $logger && $logger->debug('nacos send '.$type.' beat success!', compact('instance'));
            } else {
                $logger && $logger->error('nacos send '.$type.' beat fail!', compact('instance'));
            }
            sleep($config->get('nacos.client.'.$type.'.beat_interval', 5));
        }
    }

    public function isEnable($server): bool
    {
        $config = $this->container->get(ConfigInterface::class);
        return $config->get('nacos.enable', true);
    }
}
