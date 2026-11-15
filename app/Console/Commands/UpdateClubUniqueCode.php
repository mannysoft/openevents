<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Event;
use App\Models\LoungeClub;

class UpdateClubUniqueCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clubs:generate-unique-code';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate unique codes for clubs that do not have one';

    /**
     * Execute the console command.s
     *
     * @return mixed
     */
    public function handle()
    {
        // Generate unique codes for clubs that do not have one
        $clubsWithoutUniqueCode = LoungeClub::whereNull('unique_code')->get();
        // $clubsWithoutUniqueCode = LoungeClub::all();
        foreach ($clubsWithoutUniqueCode as $club) {
            $club->unique_code = $this->generateUniqueCode($club->name);
            $club->save();
            $this->info('Generated unique code for club: ' . $club->name);
        }
    }

    private function generateUniqueCode($name)
    {
        // Generate a unique code based on the club name and a random string
        $randomString = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
        return strtoupper(substr($name, 0, 3)) . $randomString;
    }
}
