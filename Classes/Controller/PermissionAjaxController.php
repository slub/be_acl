<?php
namespace JBartels\BeAcl\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Beuser\Controller\PermissionAjaxController as BaseController;
use JBartels\BeAcl\Exception\RuntimeException;

/**
 * This class extends the permissions module in the TYPO3 Backend to provide
 * convenient methods of editing of page permissions (including page ownership
 * (user and group)) via new AjaxRequestHandler facility
 */
class PermissionAjaxController extends BaseController
{

    /**
     * View object
     * @var view \TYPO3\CMS\Fluid\View\StandaloneView
     */
    protected $view;

    /**
     * Extension path
     * @var string
     */
    protected $extPath;

    /**
     * ACL table
     * @var string
     */
    protected $table = 'tx_beacl_acl';

    /**
     * Set the extension path
     * @param string $extPath
     */
    protected function setExtPath($extPath = null)
    {
        $this->extPath = empty($extPath) ? ExtensionManagementUtility::extPath('be_acl') : $extPath;
    }

    /**
     * Initialize the viewz
     */
    protected function initializeView()
    {
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setPartialRootPaths(array('default' => $this->extPath . 'Resources/Private/Partials'));
        $this->view->assign('pageId', $this->conf['page']);
    }

    /**
     * The main dispatcher function. Collect data and prepare HTML output.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        //in sysext ACL moved to dispatch() method
        $parsedBody = $request->getParsedBody();

        $this->conf = [
            'page' => $parsedBody['page'] ?? null,
            'who' => $parsedBody['who'] ?? null,
            'mode' => $parsedBody['mode'] ?? null,
            'bits' => (int)($parsedBody['bits'] ?? 0),
            'permissions' => (int)($parsedBody['permissions'] ?? 0),
            'action' => $parsedBody['action'] ?? null,
            'ownerUid' => (int)($parsedBody['ownerUid'] ?? 0),
            'username' => $parsedBody['username'] ?? null,
            'groupUid' => (int)($parsedBody['groupUid'] ?? 0),
            'groupname' => $parsedBody['groupname'] ?? '',
            'editLockState' => (int)($parsedBody['editLockState'] ?? 0),
            'new_owner_uid' => (int)($parsedBody['newOwnerUid'] ?? 0),
            'new_group_uid' => (int)($parsedBody['newGroupUid'] ?? 0),
        ];

        // Actions handled by this class
        $handledActions = ['delete_acl'];

        // Handle action
        $action = $this->conf['action'];
        if ($this->conf['page'] > 0 && in_array($action, $handledActions)) {
            return $this->handleAction($request, $action);
        } // Action handled by parent
        else {
            return parent::dispatch($request);
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param $action
     * @return mixed
     */
    protected function handleAction(ServerRequestInterface $request, $action)
    {
        $methodName = GeneralUtility::underscoredToLowerCamelCase($action);
        if (method_exists($this, $methodName)) {
            return call_user_func_array(array($this, $methodName), [$request]);
        }
    }

    /**
     * The main dispatcher function. Collect data and prepare HTML output.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function deleteAcl(ServerRequestInterface $request) : ResponseInterface
    {
        $response = new HtmlResponse('');
        $GLOBALS['LANG']->includeLLFile('EXT:be_acl/Resources/Private/Languages/locallang_perm.xlf');
        $GLOBALS['LANG']->getLL('aclUsers');

        $postData = $request->getParsedBody();
        $aclUid = !empty($postData['acl']) ? $postData['acl'] : null;

        if (!MathUtility::canBeInterpretedAsInteger($aclUid)) {
            return $this->errorResponse($response, $GLOBALS['LANG']->getLL('noAclId'), 400);
        }
        $aclUid = (int)$aclUid;
        // Prepare command map
        $cmdMap = [
            $this->table => [
                $aclUid => ['delete' => 1]
            ]
        ];

        try {
            // Process command map
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->stripslashes_values = 0;
            $tce->start(array(), $cmdMap);
            $this->checkModifyAccess($this->table, $aclUid, $tce);
            $tce->process_cmdmap();
        } catch (\Exception $ex) {
            return $this->errorResponse($response, $ex->getMessage(), 403);
        }

        $body = [
            'title' => $GLOBALS['LANG']->getLL('aclSuccess'),
            'message' => $GLOBALS['LANG']->getLL('aclDeleted')
        ];
        // Return result
        $response->getBody()->write(json_encode($body));
        return $response;
    }

    /**
     * @param $table
     * @param $id
     * @param DataHandler $tcemainObj
     */
    protected function checkModifyAccess($table, $id, DataHandler $tcemainObj)
    {
        // Check modify access
        $modifyAccessList = $tcemainObj->checkModifyAccessList($table);
        // Check basic permissions and circumstances:
        if (!isset($GLOBALS['TCA'][$table]) || $tcemainObj->tableReadOnly($table) || !is_array($tcemainObj->cmdmap[$table]) || !$modifyAccessList) {
            throw new RuntimeException($GLOBALS['LANG']->getLL('noPermissionToModifyAcl'));
        }

        // Check table / id
        if (!$GLOBALS['TCA'][$table] || !$id) {
            throw new RuntimeException(sprintf($GLOBALS['LANG']->getLL('noEditAccessToAclRecord'), $id, $table));
        }

        // Check edit access
        $hasEditAccess = $tcemainObj->BE_USER->recordEditAccessInternals($table, $id, false, false, true);
        if (!$hasEditAccess) {
            throw new RuntimeException(sprintf($GLOBALS['LANG']->getLL('noEditAccessToAclRecord'), $id, $table));
        }
    }

    /**
     * @param ResponseInterface $response
     * @param $reason
     * @param int $status
     * @return static
     */
    protected function errorResponse(ResponseInterface $response, $reason, $status = 500)
    {
        return $response->withStatus($status, $reason);
    }
}
