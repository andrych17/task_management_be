<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Creating users...');

        // Create demo user
        $demoUser = User::create([
            'name' => 'Demo User',
            'email' => 'demo@taskmanager.com',
            'password' => bcrypt('demo12345'),
        ]);

        // Create additional users
        $users = User::factory()->count(2)->create();
        $allUsers = [$demoUser, ...$users];

        $this->command->info('Creating projects...');

        $projectTemplates = [
            ['name' => 'Website Redesign', 'description' => 'Complete website redesign project'],
            ['name' => 'Mobile App Development', 'description' => 'Build cross-platform mobile application'],
            ['name' => 'API Integration', 'description' => 'Integrate third-party APIs'],
            ['name' => 'Database Migration', 'description' => 'Migrate from MySQL to PostgreSQL'],
            ['name' => 'Performance Optimization', 'description' => 'Improve application performance'],
            ['name' => 'Security Audit', 'description' => 'Conduct comprehensive security review'],
            ['name' => 'Documentation Update', 'description' => 'Update technical documentation'],
            ['name' => 'Testing Automation', 'description' => 'Implement automated testing pipeline'],
            ['name' => 'UI/UX Improvements', 'description' => 'Enhance user interface and experience'],
            ['name' => 'DevOps Setup', 'description' => 'Setup CI/CD pipeline and infrastructure'],
        ];

        $userProjects = [];
        foreach ($allUsers as $user) {
            $userProjects[$user->id] = [];
            for ($i = 0; $i < 10; $i++) {
                $template = $projectTemplates[$i];
                $userProjects[$user->id][] = Project::create([
                    'user_id' => $user->id,
                    'name' => $template['name'],
                    'description' => $template['description'],
                ]);
            }
        }

        $this->command->info('Creating tags...');

        $tagNames = ['urgent', 'backend', 'frontend', 'bug', 'feature', 'api', 'database', 'ui', 'testing', 'security'];

        $userTags = [];
        foreach ($allUsers as $user) {
            $userTags[$user->id] = [];
            foreach ($tagNames as $tagName) {
                $userTags[$user->id][] = Tag::create([
                    'user_id' => $user->id,
                    'name' => $tagName,
                ]);
            }
        }

        $this->command->info('Creating tasks...');

        $taskTemplates = [
            ['title' => 'Setup project structure', 'description' => 'Initialize project with proper folder structure', 'status' => 'done'],
            ['title' => 'Configure development environment', 'description' => 'Setup local development environment', 'status' => 'done'],
            ['title' => 'Create database schema', 'description' => 'Design and implement database tables', 'status' => 'done'],
            ['title' => 'Implement authentication', 'description' => 'Add user login and registration', 'status' => 'in-progress'],
            ['title' => 'Design user interface', 'description' => 'Create UI mockups and prototypes', 'status' => 'in-progress'],
            ['title' => 'Write API endpoints', 'description' => 'Implement RESTful API endpoints', 'status' => 'in-progress'],
            ['title' => 'Add validation rules', 'description' => 'Implement form validation', 'status' => 'todo'],
            ['title' => 'Write unit tests', 'description' => 'Create comprehensive test suite', 'status' => 'todo'],
            ['title' => 'Implement error handling', 'description' => 'Add proper error handling and logging', 'status' => 'todo'],
            ['title' => 'Optimize queries', 'description' => 'Improve database query performance', 'status' => 'todo'],
            ['title' => 'Add caching layer', 'description' => 'Implement Redis caching', 'status' => 'todo'],
            ['title' => 'Security hardening', 'description' => 'Review and fix security vulnerabilities', 'status' => 'todo'],
            ['title' => 'Code review', 'description' => 'Review code for best practices', 'status' => 'todo'],
            ['title' => 'Performance testing', 'description' => 'Conduct load and stress testing', 'status' => 'todo'],
            ['title' => 'Documentation', 'description' => 'Write API and user documentation', 'status' => 'todo'],
            ['title' => 'Deployment setup', 'description' => 'Configure production environment', 'status' => 'todo'],
            ['title' => 'Monitoring setup', 'description' => 'Setup application monitoring', 'status' => 'todo'],
            ['title' => 'Bug fixes', 'description' => 'Fix reported bugs and issues', 'status' => 'in-progress'],
            ['title' => 'Feature enhancements', 'description' => 'Implement new feature requests', 'status' => 'todo'],
            ['title' => 'Refactoring', 'description' => 'Refactor legacy code', 'status' => 'todo'],
            ['title' => 'Integration testing', 'description' => 'Test integration with third-party services', 'status' => 'todo'],
            ['title' => 'Accessibility improvements', 'description' => 'Ensure WCAG compliance', 'status' => 'todo'],
            ['title' => 'Responsive design', 'description' => 'Make UI responsive for all devices', 'status' => 'in-progress'],
            ['title' => 'Backup strategy', 'description' => 'Implement automated backup system', 'status' => 'todo'],
            ['title' => 'Data migration', 'description' => 'Migrate data from old system', 'status' => 'todo'],
            ['title' => 'User feedback', 'description' => 'Collect and analyze user feedback', 'status' => 'todo'],
            ['title' => 'Analytics integration', 'description' => 'Add analytics tracking', 'status' => 'todo'],
            ['title' => 'Email notifications', 'description' => 'Implement email notification system', 'status' => 'todo'],
            ['title' => 'Payment integration', 'description' => 'Integrate payment gateway', 'status' => 'todo'],
            ['title' => 'Final review', 'description' => 'Final review before deployment', 'status' => 'todo'],
        ];

        foreach ($allUsers as $user) {
            $projects = $userProjects[$user->id];
            $tags = $userTags[$user->id];

            for ($i = 0; $i < 30; $i++) {
                $template = $taskTemplates[$i];

                // Distribute tasks across projects (some without project)
                $projectId = $i < 25 ? $projects[$i % 10]->id : null;

                // Random due dates
                $daysOffset = rand(-10, 30);
                $dueDate = now()->addDays($daysOffset);

                $task = Task::create([
                    'user_id' => $user->id,
                    'project_id' => $projectId,
                    'title' => $template['title'],
                    'description' => $template['description'],
                    'status' => $template['status'],
                    'due_date' => $dueDate,
                ]);

                // Attach 1-3 random tags to each task
                $numTags = rand(1, 3);
                $randomTagIds = array_rand($tags, $numTags);
                if (!is_array($randomTagIds)) {
                    $randomTagIds = [$randomTagIds];
                }
                $tagIds = array_map(fn($index) => $tags[$index]->id, $randomTagIds);
                $task->tags()->attach($tagIds);
            }
        }

        $this->command->info('Seeding completed successfully!');
        $this->command->info('Demo account: demo@taskmanager.com / demo12345');
        $this->command->info('Created: 3 users, 30 projects (10 per user), 90 tasks (30 per user), 30 tags (10 per user)');
    }
}


