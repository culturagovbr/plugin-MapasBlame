<?php

namespace Tests\MapasBlame\Doubles;

use MapasBlame\Plugin;

/**
 * Sobrescreve _init() para não-operação: MapasCulturais\Module::__construct chama _init()
 * direto no construtor, e o _init() real do Plugin registra hooks globais no App
 * (mapasculturais.run:before, entity(<<*>>).remove:after, etc.) — instanciar o Plugin real
 * repetidamente nos testes acumularia esses hooks a cada instância, sem isolamento de processo.
 */
class TestablePlugin extends Plugin
{
    function _init()
    {
    }
}
