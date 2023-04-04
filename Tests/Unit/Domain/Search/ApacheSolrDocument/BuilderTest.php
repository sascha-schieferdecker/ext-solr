<?php

namespace ApacheSolrForTypo3\Solr\Tests\Unit\Domain\Search\ApacheSolrDocument;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Timo Hund <timo.hund@dkd.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use ApacheSolrForTypo3\Solr\Access\Rootline;
use ApacheSolrForTypo3\Solr\Domain\Search\ApacheSolrDocument\Builder;
use ApacheSolrForTypo3\Solr\Domain\Site\Site;
use ApacheSolrForTypo3\Solr\Domain\Variants\IdBuilder;
use ApacheSolrForTypo3\Solr\System\Solr\Document\Document;
use ApacheSolrForTypo3\Solr\Tests\Unit\UnitTest;
use ApacheSolrForTypo3\Solr\Typo3PageContentExtractor;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Testcase for the Builder of ApacheSolrDocument
 *
 * @author Timo Hund <timo.hund@dkd.de>
 */
class BuilderTest extends UnitTest
{
    /**
     * @var IdBuilder
     */
    protected $variantIdBuilderMock;

    /**
     * @var Site
     */
    protected $siteMock;

    /**
     * @var Typo3PageContentExtractor
     */
    protected $typo3PageExtractorMock;

    /**
     * @var Builder
     */
    protected $documentBuilder;

    protected function setUp(): void
    {
        /** @var $variantIdBuilderMock */
        $this->variantIdBuilderMock = $this->getDumbMock(IdBuilder::class);
        $this->siteMock = $this->getDumbMock(Site::class);
        $this->typo3PageExtractorMock = $this->getDumbMock(Typo3PageContentExtractor::class);

        /** @var $documentBuilder Builder */
        $this->documentBuilder = $this->getMockBuilder(Builder::class)->setConstructorArgs([$this->variantIdBuilderMock ])->setMethods(
            ['getExtractorForPageContent', 'getSiteByPageId', 'getPageDocumentId', 'getDocumentId']
        )->getMock();

        $this->documentBuilder->expects(self::any())->method('getExtractorForPageContent')->willReturn($this->typo3PageExtractorMock);
        $this->documentBuilder->expects(self::any())->method('getSiteByPageId')->willReturn($this->siteMock);
    }

    /**
     * @test
     */
    public function canBuildApacheSolrDocumentFromEmptyPage()
    {
        $fakePage = $this->getDumbMock(TypoScriptFrontendController::class);
        $fakeRootLine = $this->getDumbMock(Rootline::class);
        $fakeRootLine->expects(self::once())->method('getGroups')->willReturn([1]);

        $this->fakePageDocumentId('siteHash/pages/4711');
        $this->fakeTagContent([]);

        $fakePage->page = [];
        $document = $this->documentBuilder->fromPage($fakePage, 'http://www.typo3-solr.com', $fakeRootLine, '');

        self::assertInstanceOf(Document::class, $document, 'Expect to get an ' . Document::class . ' back');
        self::assertSame('siteHash/pages/4711', $document['id'], 'Builder did not use documentId from mock');
    }

    /**
     * @test
     */
    public function canSetKeywordsForApacheSolrDocument()
    {
        $fakePage = $this->getDumbMock(TypoScriptFrontendController::class);
        $fakeRootLine = $this->getDumbMock(Rootline::class);
        $fakeRootLine->expects(self::once())->method('getGroups')->willReturn([1]);

        $this->fakePageDocumentId('siteHash/pages/4711');
        $this->fakeTagContent([]);

        $fakePage->page = ['keywords' => 'foo,bar'];
        $document = $this->documentBuilder->fromPage($fakePage, 'http://www.typo3-solr.com', $fakeRootLine, '');

        self::assertSame($document['keywords'], ['foo', 'bar'], 'Could not set keywords from page document');
    }

    /**
     * @test
     */
    public function canSetEndtimeForApacheSolrDocument()
    {
        $fakePage = $this->getDumbMock(TypoScriptFrontendController::class);
        $fakeRootLine = $this->getDumbMock(Rootline::class);
        $fakeRootLine->expects(self::once())->method('getGroups')->willReturn([1]);

        $this->fakePageDocumentId('siteHash/pages/4711');
        $this->fakeTagContent([]);

        $fakePage->page = ['endtime' => 1234];
        $document = $this->documentBuilder->fromPage($fakePage, 'http://www.typo3-solr.com', $fakeRootLine, '');

        self::assertSame($document['endtime'], 1234, 'Could not set endtime from page document');
    }

    /**
     * @test
     */
    public function canSetTagFieldsForApacheSolrDocument()
    {
        $fakePage = $this->getDumbMock(TypoScriptFrontendController::class);
        $fakeRootLine = $this->getDumbMock(Rootline::class);
        $fakeRootLine->expects(self::once())->method('getGroups')->willReturn([1]);

        $this->fakePageDocumentId('siteHash/pages/4711');
        $this->fakeTagContent(['tagsH1' => 'Fake H1 content']);

        $fakePage->page = [];
        $document = $this->documentBuilder->fromPage($fakePage, 'http://www.typo3-solr.com', $fakeRootLine, '');

        self::assertSame($document['tagsH1'], 'Fake H1 content', 'Could not assign extracted h1 heading to solr document');
    }

    /**
     * @test
     */
    public function canBuildFromRecord()
    {
        $fakeRecord = ['uid' => 4711, 'pid' => 88, 'type' => 'news'];
        $type = 'news';
        $this->fakeDocumentId('testSiteHash/news/4711');

        $this->siteMock->expects(self::any())->method('getRootPageId')->willReturn(99);
        $this->siteMock->expects(self::once())->method('getDomain')->willReturn('test.typo3.org');
        $this->siteMock->expects(self::any())->method('getSiteHash')->willReturn('testSiteHash');
        $this->variantIdBuilderMock->expects(self::once())->method('buildFromTypeAndUid')->with('news', 4711)->willReturn('testVariantId');

        $document = $this->documentBuilder->fromRecord($fakeRecord, $type, 99, 'r:0');

        self::assertSame(4711, $document->uid, 'Uid field was not set as expected');
        self::assertSame(88, $document->pid, 'Pid field was not set as expected');
        self::assertSame('test.typo3.org', $document->site, 'Site field was not set as expected');
        self::assertSame('testSiteHash', $document->siteHash, 'SiteHash field was not set as expected');
        self::assertSame('testVariantId', $document->variantId, 'VariantId field was not set as expected');
        self::assertSame('r:0', $document->access, 'Access field was not set as expected');
        self::assertSame('testSiteHash/news/4711', $document->id, 'Id field was not set as expected');
        self::assertSame('news', $document->type, 'Type field was not set as expected');
        self::assertSame('EXT:solr', $document->appKey, 'appKey field was not set as expected');
    }

    /**
     * @param string $documentId
     */
    protected function fakePageDocumentId($documentId)
    {
        $this->documentBuilder->expects(self::once())->method('getPageDocumentId')->willReturn($documentId);
    }

    /**
     * @param string $documentId
     */
    protected function fakeDocumentId($documentId)
    {
        $this->documentBuilder->expects(self::once())->method('getDocumentId')->willReturn($documentId);
    }

    /**
     * @param array $tagContent
     */
    protected function fakeTagContent($tagContent = [])
    {
        $this->typo3PageExtractorMock->expects(self::once())->method('getTagContent')->willReturn($tagContent);
    }
}
