<?php
/* Copr. 2018 Curbstone Corporation MLP V0.92 */

namespace Curbstone\CardOnFile\Controller\Index;


class SaveCard extends \Curbstone\IFrame\Controller\Index\SaveCard
{

    public function execute()
    {
        $request = $this->request->getParams();
        $this->createCardOnFile($request);
    }

    /**
     * @param $response
     * @return null
     */
    protected function createCardOnFile($response)
    {
        $resultUrl = 'vault/cards/listaction';
        $this->payLog->writePaylog("Authorize Card-on-File Request Data:");
        $this->payLog->writePaylog(print_r($response, true));
        if (array_key_exists('MFRTRN', $response)) {
            switch ($response['MFRTRN']) {
                case 'UG':
                    $this->messageManager->addSuccessMessage(
                        __('Transaction Processed:  ' . $response['MFRTXT'].'.' . 'Card has been saved.')
                    );
                    $this->createVaultToken($response);
                    $this->redirect->redirect($this->response, $resultUrl);
                    break;
                case 'UN':
                    $this->messageManager->addSuccessMessage(
                        __('Transaction Processed:  ' . $response['MFRTXT'])
                    );
                    break;
                case 'UL':
                default:
                    $this->messageManager->addErrorMessage(
                        __('Field Error Code: ' . $response['MFATAL'] . ' - ' . $response['MFRTXT'])
                    );
                    break;
            }
        } else {
            $this->messageManager->addErrorMessage(__('Sorry, something went wrong. Please try again later.'));
            $this->redirect->redirect($this->response, $resultUrl);
        }
        return null;
    }
}
