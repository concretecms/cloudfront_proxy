<?php

namespace Concrete5\Cloudfront;

use Concrete\Core\Application\Application;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class CloudfrontUpdateCommand extends Command
{

    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        parent::__construct('cdn:cloudfront:update');
    }

    protected function configure()
    {
        $this->setName('cdn:cloudfront:update')
            ->setDescription('Update cloudfront IPs')
            ->addOption('force', ['f', 'y'], InputOption::VALUE_NONE, 'Force the update')
            ->addOption('quiet', 'q', InputOption::VALUE_NONE, 'Don\'t output');
    }

    /**
     * Update CDN ips
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('quiet')) {
            $output->setVerbosity($output::VERBOSITY_QUIET);
        }

        $config = $this->app['config'];

        // Get the configuration
        $endpoints = $this->getEndpoints($config['cloudfront_proxy::endpoints']);

        // Get the old IPs
        $oldIps = $config['cloudfront_proxy::ips.user'];

        // Get the new list of IPs
        $ips = $this->getIps($endpoints, $output);

        // If we should update, lets update
        if ($this->shouldApplyChanges($input, $output, $ips, $oldIps)) {
            $config->save('cloudfront_proxy::ips.user', $ips);

            // Return a success response
            return 0;
        }

        // Return a failure response
        return 1;
    }

    /**
     * Get the IPs from a url service
     * @param $urls
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return array
     */
    private function getIps($urls, OutputInterface $output)
    {
        $ips = [];
        foreach ($urls as $url) {
            $output->writeln('Downloading IPs from ["' . $url . '"]');

            if ($contents = file_get_contents($url)) {
                $data = json_decode($contents, true);

                if (isset($data['CLOUDFRONT_REGIONAL_EDGE_IP_LIST'])) {
                    $ips = array_merge($ips, Arr::flatten($data));
                } else {
                    $ips = array_merge($ips, iterator_to_array($this->filterIps($data)));
                }
            }
        }

        return array_filter($ips);
    }

    /**
     * Filter a list of IPs down to just cloudfront IPs
     * This works with the main ip-ranges endpoint to resolve only clodufront ips
     *
     * @param array $data
     * @return \Generator
     */
    protected function filterIps(array $data)
    {
        $ips = array_get($data, 'prefixes', []);

        foreach ($ips as $ip) {
            if (array_get($ip, 'service') === 'CLOUDFRONT' && $prefix = array_get($ip, 'ip_prefix')) {
                yield $prefix;
            }
        }
    }

    private function shouldApplyChanges(InputInterface $input, OutputInterface $output, array $ips, array $oldIps)
    {
        // There is no difference between the two arrays
        if (!$ips) {
            $output->writeln('No IPs were found.');
            return false;
        }

        // Diff the old IP array with the new one
        $addIps = array_diff($ips, $oldIps);
        $removeIps = array_diff($oldIps, $ips);

        // There is no difference between the two arrays
        if (!$addIps && !$removeIps) {
            $output->writeln('No changes detected.');
            return true;
        }

        // If we have IP's being added
        if ($addIps) {
            $output->writeln(['', 'Adding IPs:']);
            $output->writeln($this->indented($addIps));
        }

        // If we have IP's being removed
        if ($removeIps) {
            $output->writeln(['', 'Removing IPs:']);
            $output->writeln($this->indented($removeIps));
        }

        // Output a general count of IPs
        $output->writeln(['', 'Leaving us with ' . count($ips) . ' IPs remaining.', '']);


        // If the user has forced this to update
        if ($input->hasOption('force')) {
            return true;
        }

        // Confirm with the user
        $question = new ConfirmationQuestion('Do you want to apply these changes? ', false);
        $question->setAutocompleterValues(['yes', 'no']);

        /** @var \Symfony\Component\Console\Helper\QuestionHelper $questionHelper */
        return (bool)$this->getHelper('question')->ask($input, $output, $question);
    }

    private function indented(array $data, $spaces = 4)
    {
        foreach ($data as &$item) {
            $item = str_repeat(' ', $spaces) . $item;
        }

        return $data;
    }

    /**
     * Get the configured endpoints
     *
     * @param array $config
     * @return array
     */
    protected function getEndpoints(array $config)
    {
        return (array) array_get($config, 'cloudfront-tools') ?: [array_get($config, 'fallback')];
    }

}
