<?php
use MapasCulturais\i;
$this->import('
    blame-table
    mc-tab
');
?>

<mc-tab cache key="blame" label="Logs do sistema" slug="blame" icon="log">
    <blame-table :user-id="entity.id"></blame-table>
</mc-tab>