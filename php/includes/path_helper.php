<?php
/**
 * Path Helper (AMORA-style)
 * Resolves relative base path from any file to project root for assets and includes.
 *
 * Usage:
 *   require_once __DIR__ . '/../includes/path_helper.php';
 *   $basePath = getBasePath(__FILE__);
 *   <link href="<?php echo $basePath; ?>css/serve_asset.php?file=style.css">
 */

if (!function_exists('getBasePath')) {
    /**
     * Get the base path to the project root from the current file location.
     * @param string $currentFile __FILE__ from the calling script
     * @return string Relative path (e.g. '../../' or '../../../')
     */
    function getBasePath($currentFile)
    {
        $currentDir = dirname($currentFile);
        // Project root: from php/includes/ go up 2 levels = NAIG root
        $projectRoot = realpath(dirname(__DIR__, 2));
        $currentDirReal = realpath($currentDir);

        if (!$currentDirReal || !$projectRoot) {
            $normalizedPath = str_replace('\\', '/', $currentFile);
            $depth = substr_count(dirname($normalizedPath), '/');
            return str_repeat('../', max(0, $depth));
        }

        $relativePath = str_replace($projectRoot . DIRECTORY_SEPARATOR, '', $currentDirReal);
        $depth = empty($relativePath) ? 0 : (substr_count($relativePath, DIRECTORY_SEPARATOR) + 1);
        return str_repeat('../', $depth);
    }
}
