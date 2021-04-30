<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

/**
 * Search for Terminus plugins to install.
 * @package Pantheon\Terminus\Commands\Self\Plugin
 * @TODO Limit the search to include only Packagist projects with versions compatible with the currently installed Terminus version.
 * @TODO Bonus: Add the ability to search and prompt to install new plugins.
 * @TODO Keep an internal registry of approved third-party plugins.
 * @TODO Do lookup if given a plugin name and not a project name, prompt OK for match, install
 */
class SearchCommand extends PluginBaseCommand
{
    const APPROVED_PROJECTS = 'terminus-plugin-project/terminus-pancakes-plugin';
    const NO_PLUGINS_MESSAGE = 'No plugins have met your criterion.';
    const OFFICIAL_PLUGIN_AUTHOR = 'pantheon-systems';
    const SEARCH_COMMAND = 'composer search -t terminus-plugin {keyword}';

    /**
     * Search for available Terminus plugins.
     *
     * @command self:plugin:search
     * @aliases self:plugin:find self:plugin:locate
     *
     * @param string $keyword A search string used to query for plugins
     *
     * @field-labels
     *     name: Name
     *     status: Status
     *     description: Description
     * @return RowsOfFields
     *
     * @usage <plugin> Searches for Terminus plugins with "plugin" in the name.
     */
    public function search($keyword)
    {
        $command = str_replace('{keyword}', $keyword, self::SEARCH_COMMAND);
        $results = explode(
            PHP_EOL,
            str_replace(' - ', ' ', trim($this->runCommand($command)['output']))
        );

        $projects = array_map(
            function ($message) {
                list($project, $description) = explode(' ', $message, 2);
                return [
                    'name' => $project,
                    'status' => self::checkStatus($project),
                    'description' => $description,
                ];
            },
            array_filter(
                $results,
                function ($message) {
                    list($project) = explode(' ', $message, 1);
                    return preg_match('#^[^/]*/[^/]*$#', $project);
                }
            )
        );

        if (empty($projects)) {
            $this->log()->warning(self::NO_PLUGINS_MESSAGE);
        }

        // Output the plugin list in table format.
        asort($projects);
        return new RowsOfFields($projects);
    }

    /**
     * Check for minimum plugin command requirements.
     * @hook validate self:plugin:search
     */
    public function validate()
    {
        $this->checkRequirements();
    }

    /**
     * Check the project status on Packagist.
     *
     * @param string $project Project name
     * @return string Project status
     */
    protected static function checkStatus($project)
    {
        if (preg_match('#^'. self::OFFICIAL_PLUGIN_AUTHOR . '/#', $project)) {
            return 'Official';
        }

        if (in_array($project, explode('|', self::APPROVED_PROJECTS))) {
            return 'Approved';
        }

        return 'Unofficial';
    }
}