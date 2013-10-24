<?php

namespace Oro\Bundle\SearchBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SearchBundle\Datagrid\SearchDatagridManager;
use Oro\Bundle\SearchBundle\Provider\ResultStatisticsProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class SearchController extends Controller
{
    /**
     * @Route("/advanced-search", name="oro_search_advanced")
     *
     * @Acl(
     *      id="oro_search",
     *      type="action",
     *      label="Search",
     *      group_name=""
     * )
     */
    public function ajaxAdvancedSearchAction()
    {
        return $this->getRequest()->isXmlHttpRequest()
            ? new JsonResponse(
                $this->get('oro_search.index')->advancedSearch(
                    $this->getRequest()->get('query')
                )->toSearchResultData()
            )
            : $this->forward('OroSearchBundle:Search:searchResults');
    }

    /**
     * Show search block
     *
     * @Route("/search-bar", name="oro_search_bar")
     * @Template("OroSearchBundle:Search:searchBar.html.twig")
     * @AclAncestor("oro_search")
     */
    public function searchBarAction()
    {
        return array(
            'entities'     => $this->get('oro_search.index')->getAllowedEntitiesListAliases(),
            'searchString' => $this->getRequest()->get('searchString'),
            'fromString'   => $this->getRequest()->get('fromString'),
        );
    }

    /**
     * @param  string       $from
     * @param  string       $string
     * @return DatagridView
     *
     * @AclAncestor("oro_search")
     */
    protected function getSearchResultsDatagridView($from, $string)
    {
        /** @var $datagridManager SearchDatagridManager */

//        $datagridManager->setSearchEntity($from);
//        $datagridManager->setSearchString($string);
//        $datagridManager->getRouteGenerator()->setRouteParameters(
//            array(
//                'from'   => $from,
//                'search' => $string,
//            )
//        );

        return '';
    }

    /**
     * Show search results
     *
     * @Route("/", name="oro_search_results")
     * @Template("OroSearchBundle:Search:searchResults.html.twig")
     *
     * @AclAncestor("oro_search")
     */
    public function searchResultsAction(Request $request)
    {
        $from   = $request->get('from');
        $string = $request->get('search');

        $datagridView = $this->getSearchResultsDatagridView($from, $string);

        /** @var $resultProvider ResultStatisticsProvider */
        $resultProvider = $this->get('oro_search.provider.result_statistics_provider');

        return array(
            'from'           => $from,
            'searchString'   => $string,
            'groupedResults' => $resultProvider->getGroupedResults($string),
            'datagrid'       => $datagridView
        );
    }

    /**
     * Return search results in json for datagrid
     *
     * @Route("/ajax", name="oro_search_results_ajax")
     *
     * @AclAncestor("oro_search")
     */
    public function searchResultsAjaxAction(Request $request)
    {
        $from   = $request->get('from');
        $string = $request->get('search');

        $datagridView = $this->getSearchResultsDatagridView($from, $string);

        return $this->get('oro_grid.renderer')->renderResultsJsonResponse($datagridView);
    }
}
