<?php

namespace App\Console;
use \App\Jobs\RealTimeData;
use \App\Jobs\TrafficData;
use \App\Jobs\CDRData;
use \App\Jobs\SBBAuthorizationNotification;
use \App\Jobs\TriggerTrafficNotifications;
use \App\Jobs\LocationSubscriptionMissing;
use App\Http\Services\RadminService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\JxSubscriptionData;
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function() {
            (new RadminService())->sync();
        })->twiceDaily()->name('radmin-sync')->withoutOverlapping();

        $schedule->call(function() {
            (new RadminService())->syncMasterData();
        })->hourly()->name('radmin-masterdata-sync')->withoutOverlapping();

        $schedule->call(function(){
            dispatch(new RealTimeData());
        })->everyTenMinutes()->name('realtime')->withoutOverlapping();

        $schedule->call(function(){
           dispatch(new TrafficData()); 
        })->dailyAt('03:10')->name('traffic')->withoutOverlapping();

        $schedule->call(function(){
           dispatch(new CDRData());
        })->dailyAt('03:10')->name('cdr')->withoutOverlapping();

        $schedule->call(function(){
            dispatch(new JxSubscriptionData());
        })->daily()->name('subscriptions')->withoutOverlapping();
		
		$schedule->call(function(){
            dispatch(new SBBAuthorizationNotification());
        })->weekly()->mondays()->at('05:00')->name('SBBAuthorizationNotification')->withoutOverlapping();
        
        $schedule->call(function() {
            dispatch(new TriggerTrafficNotifications());
        })->everyTenMinutes()->name('trigger-traffic-notifications')->withoutOverlapping();
        
        $schedule->call(function(){
            dispatch(new LocationSubscriptionMissing());
        })->daily()->name('location-subscription-missing')->withoutOverlapping();
               
    }
}
