<?php

namespace Nng\Nnfesubmit\Wizicons;


class AddContentElementWizicon {

		/**
         * Processing the wizard items array
         *
         * @param array $wizardItems The wizard items
         * @return array Modified array with wizard items
         */
        function proc($wizardItems)     {
                $wizardItems['plugins_tx_nnfesubmit'] = array(
                        'icon' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('nnfesubmit') . 'Resources/Public/Icons/wizicon.png',
                        'title' => 'NN Frontend Submission',
                        'description' => 'Formular zur Eintragen von Datensätzen über das Frontend.',
                        'params' => '&defVals[tt_content][CType]=list&&defVals[tt_content][list_type]=nnfesubmit_nnfesubmit'
                );

                return $wizardItems;
        }
        
}

?>