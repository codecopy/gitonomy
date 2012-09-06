<?php

/**
 * This file is part of Gitonomy.
 *
 * (c) Alexandre Salomé <alexandre.salome@gmail.com>
 * (c) Julien DIDIER <genzo.wm@gmail.com>
 *
 * This source file is subject to the GPL license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gitonomy\Bundle\FrontendBundle\Templating;

use Symfony\Bundle\TwigBundle\TwigEngine as BaseTwigEngine;

class TwigEngine extends BaseTwigEngine
{
    public function loadTemplate($name)
    {
        return $this->load($name);
    }
}
