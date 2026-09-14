<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            ['name' => 'Aarav Sharma', 'role' => 'Founder & CEO', 'bio' => 'Visionary leader building Believoo into a global technology group.', 'order' => 1, 'is_active' => true],
            ['name' => 'Priya Mehta', 'role' => 'Chief Technology Officer', 'bio' => 'Oversees engineering, cloud architecture and product strategy.', 'order' => 2, 'is_active' => true],
            ['name' => 'Rohan Iyer', 'role' => 'Head of Design', 'bio' => 'Crafts premium user experiences and brand identity across products.', 'order' => 3, 'is_active' => true],
            ['name' => 'Sneha Kapoor', 'role' => 'Growth Lead', 'bio' => 'Drives SEO, AdSense and digital marketing initiatives.', 'order' => 4, 'is_active' => true],
            ['name' => 'Vikram Patel', 'role' => 'DevOps Engineer', 'bio' => 'Manages cloud infrastructure, Proxmox clusters and CI/CD pipelines.', 'order' => 5, 'is_active' => true],
            ['name' => 'Ananya Gupta', 'role' => 'Project Manager', 'bio' => 'Ensures every project is delivered on time and beyond expectations.', 'order' => 6, 'is_active' => true],
        ];

        foreach ($members as $member) {
            Team::updateOrCreate(['name' => $member['name']], $member);
        }
    }
}
