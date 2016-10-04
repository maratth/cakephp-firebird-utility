<?php
namespace CakephpFirebird\View\Helper;

use Cake\View\Helper\HtmlHelper;

/**
 * Description of FirebirdHelper
 *
 * @author Valentin REYDY <valentin@microtec.fr>
 */
class FirebirdHelper extends HtmlHelper {
    
    public function imageFromBlob($blob, $option = []) {
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
