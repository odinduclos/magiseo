<?php

namespace Magiseo\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MagiseoUserBundle extends Bundle
{
  public function getParent()
  {
    return 'FOSUserBundle';
  }
}
