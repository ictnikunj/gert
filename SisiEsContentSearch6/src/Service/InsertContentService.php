<?php

namespace Sisi\SisiEsContentSearch6\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Sisi\Search\Service\CategorieService;
use Sisi\Search\Service\ContextService;
use Sisi\Search\Service\CriteriaService;
use Sisi\SisiEsContentSearch6\Core\Fields\Bundle\ContentFieldsEntity;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Elasticsearch\Client;
use Sisi\Search\Service\ProgressService;
use Shopware\Core\Content\Category\Aggregate\CategoryTranslation\CategoryTranslationCollection;

class InsertContentService
{

    /**
     * @param ContainerInterface $container
     * @param string $language
     * @param string $shop
     * @param array | null $config
     * @return array
     *
     * @SuppressWarnings(PHPMD)
     */
    public function mergeContent(ContainerInterface $container, string $language, string $shop, ?array $config): array
    {
        $contextService = new ContextService();
        $criteriaHandler = new CriteriaService();
        $categorieHandler = new CategorieService();
        $categorieTree = [];
        $context = $contextService->getContext();
        $categoryhandler = $container->get('category.repository');
        $criteria = new Criteria();
        $criteria->addAssociation('translations');
        $criteria->addAssociation('cmsPage');
        $criteria->addAssociation('cmsPage');
        $criteria->addAssociation('cmsPage.sections');
        $criteria->addAssociation('cmsPage.sections.translations');
        $criteria->addAssociation('cmsPage.sections.blocks');
        $criteria->addAssociation('cmsPage.sections.blocks.translations');
        $criteria->addAssociation('cmsPage.sections.blocks.slots');
        $criteria->addAssociation('cmsPage.sections.blocks.slots.translations');
        $criteria->addFilter(new EqualsFilter('active', 1));
        $categoryEnties = $categoryhandler->search($criteria, $context)->getEntities()->getElements();
        $saleschannel = $container->get('sales_channel.repository');
        $context = $contextService->getContext();
        $criteriaChannel = new Criteria();
        $shop = str_replace("shop=", "", $shop);
        $criteriaHandler->getMergeCriteriaForSalesChannel($criteriaChannel, $shop);
        $salechannelItem = $saleschannel->search($criteriaChannel, $context)->getEntities()->getElements();
        $salechannelItem = array_shift($salechannelItem);
        $categorieId = $salechannelItem->getNavigationCategoryId();
        $footerId = $salechannelItem->getFooterCategoryId();
        $categorieTree = $categorieHandler->getAllCategories($container, $categorieId);
        $excludids = [];
        $excludeCategories = [];
        if ($footerId != null) {
            $categorieFooterTree = $categorieHandler->getAllCategories($container, $footerId);
            $categorieTree = array_merge($categorieTree, $categorieFooterTree);
        }
        $serviceId = $salechannelItem->getServiceCategoryId();
        if ($serviceId != null) {
            $categorieFooterTree = $categorieHandler->getAllCategories($container, $serviceId);
            $categorieTree = array_merge($categorieTree, $categorieFooterTree);
        }
        $content = [];
        if (array_key_exists('outIds', $config)) {
            $excludids = explode("\n", $config['outIds']);
        }
        if (array_key_exists('outCategorien', $config)) {
            $excludeCategories = explode("\n", $config['outCategorien']);
        }
        foreach ($categoryEnties as $categoykey => $category) {
            $cmsEntyCollection = $category->getCmsPage();
            $categoryName = $this->getCategorieName($category->getTranslations(), $language);
            if ($categoryName === null) {
                $categoryName = $category->getName();
            }
            if ($cmsEntyCollection != null && in_array($categoykey, $categorieTree) && $cmsEntyCollection->getSections() != null) {
                foreach ($cmsEntyCollection->getSections()->getElements() as $cms) {
                    $slots = $cms->getBlocks()->getSlots();
                    $slotsValues = $slots->getElements();
                    foreach ($slotsValues as $slot) {
                        $configs = $slot->getTranslations()->getElements();
                        if (is_array($configs)) {
                            foreach ($configs as $configValue) {
                                $config = $configValue->getConfig();
                                if ($config != null) {
                                    if (array_key_exists("content", $config) && !empty($categoykey) && (strtolower($configValue->getLanguageId()) == strtolower($language))) {
                                        $ids = $slot->getId();
                                        if ($config["content"]["value"] !== 'category.description' && !in_array($ids, $excludids) && !in_array($categoryName, $excludeCategories)) {
                                            $content[$ids]["content"] = $config["content"];
                                            $content[$ids]['id'] = $ids;
                                            $content[$ids]["categorie_ids"] = $categoykey;
                                            $content[$ids]["CMS_Titel"] = $categoryName;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $content;
    }

    /**
     * @param CategoryTranslationCollection $collection
     * @param string $language
     * @return string|null
     */

    private function getCategorieName(CategoryTranslationCollection $collection, string $language)
    {
        $return = null;
        foreach ($collection->getElements() as $element) {
            if (strtolower($element->getLanguageId()) === strtolower($language)) {
                return $element->getName();
            }
        }
        return $return;
    }

    /**
     * @param Client $client
     * @param array $params
     * @return array
     */
    public function createIndex(client $client, array $params)
    {
        return $client->indices()->create($params);
    }

    /**
     * @param array $content
     * @param array $parameters
     * @param Client $client
     * @param OutputInterface | null $output
     * @param ContentFieldsEntity $backendConfig
     * @return void
     */
    public function insertContentToES(array $content, array $parameters, Client $client, ?OutputInterface $output, ContentFieldsEntity $backendConfig)
    {
        $outputheanlder = new OutPutService();
        $outputheanlder->write($output, "content indexierung start");
        foreach ($content as $contentItem) {
            if (!empty($contentItem["content"]["value"])) {
                $fields["CMS_Source"] = $contentItem["content"]["source"];
                $value = $this->stripContent($contentItem["content"]["value"], $backendConfig);
                if ($value !== 'category.description' && !empty($value)) {
                    $fields["CMS_Content"] = $this->stripContent($contentItem["content"]["value"], $backendConfig);
                    $fields["categorie_ids"] = $contentItem["categorie_ids"];
                    $fields["CMS_Titel"] = $contentItem["CMS_Titel"];
                    $params = [
                        'index' => $parameters['esIndex'],
                        'id' => $contentItem["id"],
                        'body' => $fields
                    ];
                    $client->index($params);
                }
            }
        }
    }

    /**
     * @param string $content
     * @param ContentFieldsEntity $config
     * @return string
     */
    public function stripContent(string $content, ContentFieldsEntity $config)
    {
        $stripTags = '';
        if (!empty($config->getPattern()) && !empty($config->getFormat())) {
            $stripTagsValues = explode(",", $config->getPattern());
            foreach ($stripTagsValues as $stripTagsValue) {
                $stripTags .= "<" . $stripTagsValue . ">";
            }
        }
        if (!empty($stripTags)) {
            return strip_tags($content, $stripTags);
        } else {
            return strip_tags($content);
        }
    }

    /**
     * @param array $config
     * @param string $index
     * @return void
     */
    public function deleteEsIndex(array $config, string $index): void
    {
        $index = "content_" . $index;
        $hostvalue = $config['host'];
        $hostvalues = explode("\n", $hostvalue);
        $command = "curl -XDELETE " . $hostvalues[0] . "/" . $index;
        shell_exec($command);
    }
}
