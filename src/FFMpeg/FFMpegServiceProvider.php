<?php

/*
 * This file is part of PHP-FFmpeg.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FFMpeg;

use Doctrine\Common\Cache\ArrayCache;
use Illuminate\Support\ServiceProvider;

class FFMpegServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public function boot()
    {
        // Publish a config file
        $this->publishes([
          __DIR__.'/../config/config.php' => config_path('ffmpeg-config.php')
        ], 'config');
    }

    public function register()
    {
        $app = $this->app;
        $app['ffmpeg.configuration'] = array();
        $app['ffmpeg.default.configuration'] = array(
          'ffmpeg.threads'   => 4,
          'ffmpeg.timeout'   => 300,
          'ffmpeg.binaries'  => array('avconv', 'ffmpeg'),
          'ffprobe.timeout'  => 30,
          'ffprobe.binaries' => array('avprobe', 'ffprobe'),
        );
        $app['ffmpeg.logger'] = null;

        $app['ffmpeg.configuration.build'] = $app->share(function ($app) {
            return array_replace($app['ffmpeg.default.configuration'], $app['ffmpeg.configuration']);
        });

        $app['ffmpeg'] = $app['ffmpeg.ffmpeg'] = $app->share(function ($app) {
            $configuration = $app['ffmpeg.configuration.build'];

            if (isset($configuration['ffmpeg.timeout'])) {
                $configuration['timeout'] = $configuration['ffmpeg.timeout'];
            }

            return FFMpeg::create($configuration, $app['ffmpeg.logger'], $app['ffmpeg.ffprobe']);
        });

        $app['ffprobe.cache'] = $app->share(function () {
            return new ArrayCache();
        });

        $app['ffmpeg.ffprobe'] = $app->share(function (Application $app) {
            $configuration = $app['ffmpeg.configuration.build'];

            if (isset($configuration['ffmpeg.timeout'])) {
                $configuration['timeout'] = $configuration['ffprobe.timeout'];
            }

            return FFProbe::create($configuration, $app['ffmpeg.logger'], $app['ffprobe.cache']);
        });
    }
}
