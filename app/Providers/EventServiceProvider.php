<?php

namespace App\Providers;

use Filament\Actions\Imports\Events\ImportStarted;
use Filament\Actions\Imports\Events\ImportEnded;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Register import event listeners
        Event::listen(ImportStarted::class, function (ImportStarted $event) {
            // Access the import through the method, not directly
            $importId = method_exists($event, 'getImport') 
                ? $event->getImport()->id 
                : 'unknown';
            
            Log::info("Import started: " . $importId);
            
            // Try to get import using reflection as a fallback
            try {
                $reflection = new \ReflectionClass($event);
                if ($reflection->hasProperty('import')) {
                    $property = $reflection->getProperty('import');
                    $property->setAccessible(true);
                    $import = $property->getValue($event);
                    
                    // Update status to processing
                    $import->status = 'processing';
                    $import->save();
                    
                    Log::info("Updated import {$import->id} status to processing");
                }
            } catch (\Exception $e) {
                Log::error("Failed to update import status: " . $e->getMessage());
            }
        });
        
        Event::listen(ImportEnded::class, function (ImportEnded $event) {
            // Access the import through the method, not directly
            $importId = method_exists($event, 'getImport') 
                ? $event->getImport()->id 
                : 'unknown';
                
            Log::info("Import ended: " . $importId);
            
            // Try to get import using reflection as a fallback
            try {
                $reflection = new \ReflectionClass($event);
                if ($reflection->hasProperty('import')) {
                    $property = $reflection->getProperty('import');
                    $property->setAccessible(true);
                    $import = $property->getValue($event);
                    
                    // Ensure status is completed
                    $import->status = 'completed';
                    $import->save();
                    
                    Log::info("Updated import {$import->id} status to completed");
                }
            } catch (\Exception $e) {
                Log::error("Failed to update import status: " . $e->getMessage());
            }
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
} 