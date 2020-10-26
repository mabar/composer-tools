<?php declare(strict_types = 1);

namespace Mabar\ComposerTools;

use Nette\Utils\Json;
use Orisai\Exceptions\Logic\InvalidArgument;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\SingleCommandApplication;
use function array_key_exists;
use function assert;
use function basename;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function is_string;
use function sprintf;

final class RecreateComposerJson
{

	private const ARG_INSTALLED_JSON = 'installed-json';

	private const ARG_COMPOSER_JSON = 'composer-json';

	private SingleCommandApplication $app;

	public function __construct()
	{
		$this->app = new SingleCommandApplication();
		$this->app
			->addArgument(self::ARG_INSTALLED_JSON, InputArgument::REQUIRED)
			->addArgument(self::ARG_COMPOSER_JSON, InputArgument::OPTIONAL)
			->setCode(function (InputInterface $input, OutputInterface $output): int {
				$installedJson = $input->getArgument(self::ARG_INSTALLED_JSON);
				assert(is_string($installedJson));

				$composerJson = $input->getArgument(self::ARG_COMPOSER_JSON);
				assert(is_string($composerJson) || $composerJson === null);

				return $this->recreate($installedJson, $composerJson, $output);
			});
	}

	public function run(): int
	{
		return $this->app->run();
	}

	private function recreate(string $installedJsonPath, ?string $composerJsonPath, OutputInterface $output): int
	{
		if (!file_exists($installedJsonPath)) {
			throw InvalidArgument::create()
				->withMessage(sprintf(
					'File %s not found at path %s.',
					basename($installedJsonPath),
					dirname($installedJsonPath),
				));
		}

		if ($composerJsonPath === null) {
			$composerJsonPath = dirname($installedJsonPath, 3) . '/composer.json';
		}

		$composerJsonDir = dirname($composerJsonPath);

		if (file_exists($composerJsonPath)) {
			throw InvalidArgument::create()
				->withMessage(sprintf(
					'File %s already exists at path %s.',
					basename($composerJsonPath),
					$composerJsonDir,
				));
		}

		if (!file_exists($composerJsonDir) || !is_dir($composerJsonDir)) {
			throw InvalidArgument::create()
				->withMessage(sprintf(
					'Directory %s does not exist.',
					$composerJsonDir,
				));
		}

		$installedContent = Json::decode(file_get_contents($installedJsonPath), Json::FORCE_ARRAY);
		$composerJsonContent = $this->rebuild($installedContent);

		file_put_contents($composerJsonPath, Json::encode($composerJsonContent, Json::PRETTY));

		$output->writeln(sprintf(
			'<info>composer.json generated at path %s</info>',
			$composerJsonDir,
		));

		return 0;
	}

	/**
	 * @param array<mixed> $installed
	 * @return array<mixed>
	 */
	private function rebuild(array $installed): array
	{
		$dependencies = [];
		$repositories = [];

		foreach ($installed as $package) {
			$version = $package['version'];

			if (!array_key_exists('dist', $package) && $package['source']['type'] === 'git') {
				$version .= '#' . $package['source']['reference'];
			}

			$dependencies[] = [
				$package['name'] => $version,
			];

			if (!array_key_exists('dist', $package) && $package['source']['type'] === 'git') {
				$repositories[] = [
					'type' => 'git',
					'url' => $package['source']['url'],
				];
			}
		}

		$composer = [
			'name' => 'vendor/project',
			'type' => 'project',
			'minimum-stability' => 'dev',
			'prefer-stable' => true,
			'require' => $dependencies,
		];

		if ($repositories !== []) {
			$composer['repositories'] = $repositories;
		}

		return $composer;
	}

}
