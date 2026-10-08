<?php

declare(strict_types=1);

namespace Odden\MailBuilder;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Odden\MailBuilder\Audit\EmailPreFlightAuditor;
use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Compilers\PlainTextExtractor;
use Odden\MailBuilder\Conditions\SlotVisibilityEvaluator;
use Odden\MailBuilder\MergeTags\MergeTagInterpolator;
use Odden\MailBuilder\MergeTags\MergeTagRegistry;
use Odden\MailBuilder\Presets\PresetRegistry;
use Odden\MailBuilder\Tracking\EmailTrackingPipeline;

class MailBuilderServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mail-builder.php', 'mail-builder');

        $this->app->singleton(EmailSlotCompiler::class, function ($app): EmailSlotCompiler {
            return new EmailSlotCompiler($app['view'], $app['config']->get('mail-builder.defaults', []));
        });

        $this->app->singleton(PlainTextExtractor::class, function (): PlainTextExtractor {
            return new PlainTextExtractor;
        });

        $this->app->singleton(PresetRegistry::class, function (): PresetRegistry {
            return new PresetRegistry;
        });

        $this->app->singleton(MergeTagInterpolator::class, function (): MergeTagInterpolator {
            return new MergeTagInterpolator;
        });

        $this->app->singleton(MergeTagRegistry::class, function ($app): MergeTagRegistry {
            return new MergeTagRegistry($app[MergeTagInterpolator::class]);
        });

        $this->app->singleton(EmailPreFlightAuditor::class, function ($app): EmailPreFlightAuditor {
            return new EmailPreFlightAuditor($app[EmailSlotCompiler::class]);
        });

        $this->app->singleton(EmailTrackingPipeline::class, function (): EmailTrackingPipeline {
            return new EmailTrackingPipeline;
        });

        $this->app->singleton(SlotVisibilityEvaluator::class, function (): SlotVisibilityEvaluator {
            return new SlotVisibilityEvaluator;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'mail-builder');

        if ((bool) config('mail-builder.editor.routes.enabled', false)) {
            Route::middleware((array) config('mail-builder.editor.routes.middleware', ['web']))
                ->prefix((string) config('mail-builder.editor.routes.prefix', 'mail-builder/editor'))
                ->group(__DIR__.'/../routes/editor.php');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/mail-builder.php' => config_path('mail-builder.php'),
            ], 'mail-builder-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/mail-builder'),
            ], 'mail-builder-views');

            $this->publishes([
                __DIR__.'/../resources/js/editor' => public_path('vendor/mail-builder/editor'),
            ], 'mail-builder-assets');
        }
    }
}
