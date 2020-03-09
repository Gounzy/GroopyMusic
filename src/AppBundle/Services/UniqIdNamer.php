<?php

namespace AppBundle\Services;

use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\NamerInterface;
use Vich\UploaderBundle\Naming\ConfigurableInterface;

/**
 * Namer using a random base64 string. The resulting name will contain lower- and uppercase alphanumeric
 * characters, '-' and '_', so it can be safely used in URLs.
 *
 * @author Keleti Márton <tejes@hac.hu>
 */
class UniqIdNamer implements NamerInterface, ConfigurableInterface
{
    const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';

    /** @var int Length of the resulting name. 10 can be decoded to a 64-bit integer. */
    protected $length = 10;

    /**
     * Injects configuration options.
     *
     * @param array $options Options for this namer. The following options are accepted:
     *                       - length: the length of the resulting name.
     */
    public function configure(array $options)
    {
        if (isset($options['length'])) {
            $this->length = $options['length'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function name($object, PropertyMapping $mapping):string
    {
        $file = $mapping->getFile($object);

        $name = '';
        for ($i = 0; $i < $this->length; ++$i) {
            $name .= $this->getRandomChar();
        }

        if ($extension = $file->getClientOriginalExtension()) {
            $name = "$name.$extension";
        }

        return $name;
    }

    protected function getRandomChar()
    {
        return self::ALPHABET[random_int(0, 63)];
    }
}
