<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GitHubUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:github-activity {username : The GitHub username to fetch activity for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch GitHub activity for a specific username';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get the username argument
        $username = $this->argument('username');

        $this->info("Fetching GitHub activity for user: $username");

        // Get GitHub API token from environment
        $githubToken = env('GITHUB_TOKEN');

        if (!$githubToken) {
            $this->error('GitHub API token is not set. Please configure it in your .env file.');
            return;
        }

        // Make the request to fetch user events
        $response = Http::withHeaders([
            'Accept' => 'application/vnd.github+json',
            'Authorization' => "Bearer {$githubToken}",
            'X-GitHub-Api-Version' => '2022-11-28',
        ])->get("https://api.github.com/users/{$username}/events");

        // Handle the response
        if ($response->successful()) {
            $events = $response->json();

            if (empty($events)) {
                $this->info("No recent activity found for user: $username");
                return;
            }

            foreach ($events as $event) {
                $type = $event['type'];
                $repo = $event['repo']['name'] ?? 'unknown repository';
                $createdAt = $event['created_at'];

                // Customize the output based on event type
                switch ($type) {
                    case 'PushEvent':
                        $commitCount = count($event['payload']['commits']);
                        $this->info("Pushed {$commitCount} commits to {$repo}");
                        break;

                    case 'IssuesEvent':
                        $action = $event['payload']['action'];
                        $this->info("{$action} an issue in {$repo} at {$createdAt}");
                        break;

                    case 'WatchEvent':
                        $this->info("Starred {$repo}");
                        break;

                    default:
                        $this->info("Event: {$type} in {$repo}");
                        break;
                }
            }
        } else {
            $this->error("Failed to fetch user activity. Status: {$response->status()}");
            $this->error($response->body());
        }
    }
}
