<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use App\Models\Experience;
use App\Models\Service;
use App\Models\Skill;
use App\Models\ContactLink;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin user
        $adminEmail = env('ADMIN_EMAIL', 'admin@portfolio.com');
        $adminPassword = env('ADMIN_PASSWORD', 'changeme123');
        
        $user = User::where('email', $adminEmail)->first();
        if ($user) {
            $user->update(['password' => Hash::make($adminPassword)]);
        } else {
            // Try to find any user and update email+password
            $user = User::first();
            if ($user) {
                $user->update([
                    'email' => $adminEmail,
                    'password' => Hash::make($adminPassword),
                ]);
            } else {
                User::create([
                    'name' => 'Admin',
                    'email' => $adminEmail,
                    'password' => Hash::make($adminPassword),
                ]);
            }
        }

        // Projects
        Project::create([
            'title' => 'Nextride — One-Way Car Rental Platform',
            'description' => 'Nextride solves a common problem for travelers in Morocco: being forced to keep a rental car for an entire stay when they only need it for a one-way intercity trip. The platform enables flexible one-way rentals — pick up in one city and return at a partner agency in another — with a flat 50 MAD drop-off fee.',
            'technologies' => ['Laravel', 'MySQL', 'Blade', 'Tailwind CSS'],
            'image' => '/images/projects/next_ride/NEXT_RIDE.png',
            'category' => 'web',
            'link' => '',
            'github' => 'https://github.com/ali-kerroum/car-rental.git',
            'videos' => ['/videos/projects/nextride/user_interface.mp4', '/videos/projects/nextride/dashboard.mp4'],
            'stats' => [],
            'skills' => ['Product design', 'Backend architecture', 'Database modeling'],
            'problem' => 'Travelers in Morocco who rent a car for an intercity trip must keep it for their entire stay — even if they only need it for the initial journey.',
            'solution' => [
                'One-way intercity rentals with cross-agency returns.',
                'Fixed transparent drop-off fee of just 50 MAD.',
                'National network of vetted partner agencies across major Moroccan cities.',
                'Simple, intuitive online booking with clear departure and return location selection.',
            ],
            'benefits' => [
                'Pay only for what you actually use.',
                'Full freedom to travel between cities without constraints.',
                'Reliable, modern experience backed by a partner agency network and efficient logistics.',
            ],
            'sort_order' => 1,
        ]);

        Project::create([
            'title' => 'Spotify Data Visualization',
            'description' => 'Executed exploratory data analysis on Spotify datasets to identify trends in artist performance, track popularity, and listener behavior.',
            'technologies' => ['Python', 'Pandas', 'Jupyter Notebook'],
            'image' => '/images/projects/dataViz_Spotify/dashboard1.jpg',
            'category' => 'data',
            'link' => '',
            'github' => 'https://github.com/ali-kerroum/Spotify-DataViz.git',
            'images' => [
                '/images/projects/dataViz_Spotify/dashboard1.jpg',
                '/images/projects/dataViz_Spotify/dashboard2.jpg',
                '/images/projects/dataViz_Spotify/dashboard3.png',
            ],
            'stats' => ['dashboards' => '3', 'analyses' => '10+'],
            'skills' => ['Data cleaning', 'EDA', 'Visualization'],
            'sort_order' => 2,
        ]);

        // Experiences
        Experience::create([
            'role' => 'Intern - Web Development & Graphic Design',
            'period' => '05/2024 - 06/2024',
            'organization' => 'Onclick Company',
            'icon' => '🎨',
            'accent' => '#fb923c',
            'points' => [
                'Designed and maintained websites using WordPress.',
                'Designed logos, Instagram posts, posters, and catalogs.',
                'Worked with the team to complete client projects on time.',
            ],
            'sort_order' => 1,
        ]);

        Experience::create([
            'role' => 'Intern - Web Development',
            'period' => '05/2025 - 06/2025',
            'organization' => 'Onclick Company',
            'icon' => '💻',
            'accent' => '#5ea0ff',
            'points' => [
                'Implemented web development features using HTML, CSS, JavaScript, PHP, and Laravel.',
                'Assisted in designing and enhancing web applications.',
                'Tested and validated features with the team.',
            ],
            'sort_order' => 2,
        ]);

        // Services
        Service::create([
            'number' => '01',
            'title' => 'Data Analysis & Visualization',
            'description' => 'Transform raw data into meaningful insights using statistical analysis and clear visual storytelling.',
            'items' => [
                'Exploratory Data Analysis (EDA)',
                'Data Cleaning & Preparation',
                'Dashboard Creation (Power BI / Matplotlib)',
                'KPI Analysis & Interpretation',
            ],
            'icon' => '📊',
            'sort_order' => 1,
        ]);

        Service::create([
            'number' => '02',
            'title' => 'Machine Learning Projects',
            'description' => 'Develop and evaluate predictive models for classification, regression, and data-driven decision support.',
            'items' => [
                'Feature Engineering',
                'Model Training & Validation',
                'Performance Optimization',
                'Scikit-learn & Python Workflows',
            ],
            'icon' => '🤖',
            'sort_order' => 2,
        ]);

        Service::create([
            'number' => '03',
            'title' => 'Full-Stack Web Development',
            'description' => 'Build responsive and scalable web applications using modern frontend and backend technologies.',
            'items' => [
                'React Frontend Development',
                'Laravel REST APIs',
                'SQL & NoSQL Database Integration',
                'Clean & Maintainable Architecture',
            ],
            'icon' => '💻',
            'sort_order' => 3,
        ]);

        // Skills
        $skillsData = [
            ['category' => 'Data Analysis & Statistics', 'icon' => '📊', 'accent' => '#5ea0ff', 'items' => ['Data Analysis', 'Statistics & Probability', 'Data Cleaning', 'Predictive Modeling', 'Data Visualization']],
            ['category' => 'Programming Languages', 'icon' => '💻', 'accent' => '#a78bfa', 'items' => ['Python', 'Java', 'C', 'PHP', 'JavaScript']],
            ['category' => 'Databases & Data Management', 'icon' => '🗄️', 'accent' => '#34d399', 'items' => ['SQL', 'NoSQL', 'MongoDB', 'Redis', 'HBase']],
            ['category' => 'Data Science & Big Data', 'icon' => '🧠', 'accent' => '#f472b6', 'items' => ['Data Mining', 'Machine Learning', 'Deep Learning', 'Big Data Fundamentals', 'ETL', 'Hadoop', 'Spark', 'Cloud (in progress)']],
            ['category' => 'Web Development', 'icon' => '🌐', 'accent' => '#38bdf8', 'items' => ['React', 'Laravel', 'HTML5', 'CSS3', 'Tailwind CSS']],
            ['category' => 'Design & Creative Skills', 'icon' => '🎨', 'accent' => '#fb923c', 'items' => ['Graphic Design', 'Logo Design', 'Brand Identity', 'UI/UX Design', 'Social Media Design', 'Adobe Photoshop', 'Adobe Illustrator', 'Canva']],
            ['category' => 'Tools & Environments', 'icon' => '⚙️', 'accent' => '#c4b5fd', 'items' => ['Jupyter Notebook', 'Pandas', 'NumPy', 'Matplotlib', 'Seaborn', 'Power BI', 'Git', 'GitHub']],
        ];

        foreach ($skillsData as $i => $skill) {
            Skill::create(array_merge($skill, ['sort_order' => $i + 1]));
        }

        // Contact Links
        ContactLink::create([
            'label' => 'GitHub',
            'href' => 'https://github.com/ali-kerroum',
            'icon_svg' => '<svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/></svg>',
            'sort_order' => 1,
        ]);

        ContactLink::create([
            'label' => 'LinkedIn',
            'href' => 'https://www.linkedin.com/in/ali-kerroum-b203332ab',
            'icon_svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
            'sort_order' => 2,
        ]);
    }
}
