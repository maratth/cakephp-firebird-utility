<?php
namespace CakephpFirebird\View\Helper;

use Cake\View\Helper\HtmlHelper;

/**
 * Description of FirebirdHelper
 *
 * @author Valentin REYDY <valentin@microtec.fr>
 */
class FirebirdHelper extends HtmlHelper {
    
    /**
     * Affiche une image depuis un blob.
     * 
     * @param string $blob
     * @param array $options
     * @return string the img tags.
     */
    public function imageFromBlob($blob, $options = []) {
        $options = array_diff_key($options, ['fullBase' => null, 'pathPrefix' => null]);
        
        $ext = 'png';
        if (isset($options['imageExt'])) {
            $ext = $options['imageExt'];
            unset($options['imageExt']);
        }
        
        $path = "data:image/$ext;base64," . base64_encode($blob);
        
        $templater = $this->templater();
        $image = $templater->format('image', [
            'url' => $path,
            'attrs' => $templater->formatAttributes($options),
        ]);
        
        return $image;
    }
    
}
