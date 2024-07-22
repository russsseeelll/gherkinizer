<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemInfo;

class SystemInfoSeeder extends Seeder
{
    public function run()
    {
        $systems = [
            [
                'name' => 'BulkLTC',
                'description' => 'A bulk upload tool to the student LTC service in the school of computing science.',
                'stack' => 'Python 3.10, Flask, HTML, CSS, Bootstrap',
                'features' => json_encode([
                    'Upload CSV / JSON / Moodle JSON',
                    'Edit uploaded files',
                    'Thorough error handling to ensure bad information doesn\'t hit the database',
                ]),
            ],
            [
                'name' => 'webedit',
                'description' => 'A tool that helps the school of maths and stats academic and admin staff book rooms, assign events, change user data, post status etc.',
                'stack' => 'PHP, CSS, HTML',
                'features' => json_encode([
                    'Complete superadmin interface to manage all users',
                    'Room bookings',
                    'Event management',
                    'User management',
                ]),
            ],
            [
                'name' => 'Unspecified',
                'description' => 'The user has not specified a system. If the user has not already disclosed this information, please include questions to identify the relevant system or context for their feature request.',
                'stack' => '',
                'features' => json_encode([]),
            ],
        ];

        foreach ($systems as $system) {
            SystemInfo::create($system);
        }
    }
}
