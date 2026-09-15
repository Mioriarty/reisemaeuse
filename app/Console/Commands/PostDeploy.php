<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Brings the app in line with whatever was just checked out.
 *
 * netcup's Git integration can run this as a deployment action, but those
 * actions are known to silently not fire on some subscriptions. So the
 * scheduler also calls it every minute with --if-changed: it compares the
 * checked-out commit against the one we last finished, and only does work when
 * they differ. Either path gets the site migrated within a minute of a push.
 */
class PostDeploy extends Command
{
    protected $signature = 'wandermaeuse:post-deploy
                            {--if-changed : Nur ausführen, wenn ein neuer Commit ausgecheckt wurde}';

    protected $description = 'Migrationen, Storage-Link und Caches nach einem Deploy';

    private const MARKER = 'deployed-sha';

    public function handle(): int
    {
        $sha = $this->currentSha();

        if ($this->option('if-changed')) {
            if ($sha === null) {
                return self::SUCCESS;
            }

            $previous = Storage::disk('local')->exists(self::MARKER)
                ? trim(Storage::disk('local')->get(self::MARKER))
                : null;

            if ($previous === $sha) {
                return self::SUCCESS;
            }
        }

        $this->info('Deploy wird abgeschlossen …');

        $this->call('migrate', ['--force' => true]);

        // A fresh checkout can wipe public/storage, so it is relinked every time.
        if (! file_exists(public_path('storage'))) {
            $this->call('storage:link');
        }

        $this->call('optimize');

        if ($sha !== null) {
            Storage::disk('local')->put(self::MARKER, $sha);
        }

        $this->info('Fertig.');

        return self::SUCCESS;
    }

    private function currentSha(): ?string
    {
        $head = base_path('.git/HEAD');

        if (! is_readable($head)) {
            return null;
        }

        $contents = trim((string) file_get_contents($head));

        if (str_starts_with($contents, 'ref: ')) {
            $ref = base_path('.git/'.substr($contents, 5));

            return is_readable($ref) ? trim((string) file_get_contents($ref)) : null;
        }

        return $contents ?: null;
    }
}
