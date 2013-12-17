<?php

namespace Barryvdh\Assetic;

use Assetic\AssetWriter;
use Assetic\Asset\AssetInterface;
use Assetic\Util\VarUtils;

/**
 * Writes assets to the filesystem.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class CheckedAssetWriter extends AssetWriter
{
    private $dir;
    private $values;

    /**
     * Constructor.
     *
     * @param string $dir    The base web directory
     * @param array  $values Variable values
     *
     * @throws \InvalidArgumentException if a variable value is not a string
     */
    public function __construct($dir, array $values = array())
    {
        foreach ($values as $var => $vals) {
            foreach ($vals as $value) {
                if (!is_string($value)) {
                    throw new \InvalidArgumentException(sprintf('All variable values must be strings, but got %s for variable "%s".', json_encode($value), $var));
                }
            }
        }

        $this->dir = $dir;
        $this->values = $values;
    }


    public function writeAsset(AssetInterface $asset)
    {
        foreach (VarUtils::getCombinations($asset->getVars(), $this->values) as $combination) {
            $asset->setValues($combination);

            $path = $this->dir.'/'.VarUtils::resolve(
                    $asset->getTargetPath(),
                    $asset->getVars(),
                    $asset->getValues()
                );

            if(!file_exists($path) || filemtime($path) <= $asset->getLastModified()){
                static::write(
                    $path,
                    $asset->dump()
                );
            }
        }
    }

}
