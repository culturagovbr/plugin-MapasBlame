<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    entity-table
');
?>

<!-- @clear-filters="clearFilters" @remove-filter="removeFilter($event)" -->
<entity-table controller="blame" endpoint="find" type="blame" identifier="blameTable" :raw-processor="rawProcessor" :headers="headers" :visible="visible" :query="query" :limit="100" show-index hide-sort hide-actions> 
    <template #filters>
        <div class="grid-12">
            <div class="field col-4 sm:col-6">
                <label> <?php i::_e('Filtar por periodo') ?></label>
                <div class="datepicker">
                    <datepicker 
                        teleport
                        :locale="locale" 
                        :weekStart="0"
                        v-model="date" 
                        :enableTimePicker='false' 
                        :dayNames="['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab']"
                        range multiCalendars multiCalendarsSolo autoApply utc></datepicker>
                </div>
                <!-- :format="dateFormat" -->
            </div>
    
            <div class="field col-4 sm:col-6">
                <label> <?php i::_e('Filtar por id da seção') ?></label>
                <input type="text" placeholder="ID da seção" v-model="sessionId" />
            </div>
        </div>
    </template>    

    <template #logTimestamp="entity">
        {{entity.entity.logTimestamp.date('2-digit year')}}
    </template>

    <template #requestTimestamp="entity">
        {{entity.entity.requestTimestamp.date('2-digit year')}}
    </template>
</entity-table>