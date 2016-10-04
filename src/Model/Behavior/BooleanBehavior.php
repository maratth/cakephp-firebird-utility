<?php
namespace CakephpFirebird\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Behavior;

/**
 * Boolean behavior.
 * 
 * Convertie les boolean Microtec 'Oui' et 'Non' en vraie boolean.
 */
class BooleanBehavior extends Behavior
{

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [];
    
    /**
     * Evenement beforeFind, Injecte une fonction pour convertir les boolean.
     * 'Oui' => true, 'Non' => false.
     * Vérifie tout les champs présent dans la requete et si un champ
     *  est un string et qu'il contient 'Oui' ou 'Non' alors cette valeur 
     *  est convertie en boolean.
     * 
     * @param Event $event
     * @param type $query
     * @param type $options
     */
    public function beforeFind(Event $event, $query, $options) {
        $query->formatResults(function($results) {
            return $results->map(function($row) {
                foreach ($row->toArray() as $key => $value) {
                    if (is_string($value) && in_array(trim($value), ['Oui', 'Non'], true)) {
                        $row[$key] = (trim($row[$key]) === 'Oui');
                    }
                }
                
                return $row;
            });
        });
    }

}
