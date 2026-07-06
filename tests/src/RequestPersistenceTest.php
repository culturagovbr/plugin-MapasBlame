<?php

namespace Tests\MapasBlame;

use MapasCulturais\Request as CoreRequest;
use Tests\Abstract\TestCase;
use Tests\MapasBlame\Doubles\TestableRequest;
use Tests\Traits\RequestFactory;
use Tests\Traits\UserDirector;

class RequestPersistenceTest extends TestCase
{
    use RequestFactory;
    use UserDirector;

    private function setAppRequestIp(string $ip): void
    {
        $psr7 = $this->requestFactory->GET('site', 'index')->withAttribute('ip_address', $ip);

        $this->app->request = new CoreRequest($psr7, 'site', 'index', []);
    }

    private function fetchBlameRequest(string $id): ?array
    {
        return $this->app->em->getConnection()->fetchAssoc('SELECT * FROM blame_request WHERE id = ?', [$id]);
    }

    private function fetchBlameLogs(string $requestId): array
    {
        return $this->app->em->getConnection()->fetchAll(
            'SELECT * FROM blame_log WHERE request_id = ? ORDER BY id',
            [$requestId]
        );
    }

    private function countBlameLogs(string $requestId): int
    {
        return count($this->fetchBlameLogs($requestId));
    }

    /**
     * Executa $callback com $_SERVER['HTTP_USER_AGENT'] definido, sempre restaurando o valor
     * anterior ao final — evita vazar a mutação do superglobal para os testes seguintes
     * (a suíte roda sem isolamento de processo).
     */
    private function withUserAgent(string $userAgent, callable $callback)
    {
        $hadKey = array_key_exists('HTTP_USER_AGENT', $_SERVER);
        $previous = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $_SERVER['HTTP_USER_AGENT'] = $userAgent;

        try {
            return $callback();
        } finally {
            if ($hadKey) {
                $_SERVER['HTTP_USER_AGENT'] = $previous;
            } else {
                unset($_SERVER['HTTP_USER_AGENT']);
            }
        }
    }

    // ===== save() =====

    function testSaveInsertsOneRowIntoBlameRequest()
    {
        $this->setAppRequestIp('203.0.113.10');

        $request = $this->withUserAgent('MapasBlameTest-Agent/1.0', function () {
            $request = new TestableRequest();
            $request->save();
            return $request;
        });

        $row = $this->fetchBlameRequest($request->id);

        $this->assertNotNull($row);
    }

    function testSavePersistsAllElevenColumns()
    {
        $this->setAppRequestIp('203.0.113.10');

        $request = $this->withUserAgent('MapasBlameTest-Agent/1.0', function () {
            $request = new TestableRequest();
            $request->save();
            return $request;
        });

        $row = $this->fetchBlameRequest($request->id);

        foreach (['id', 'ip', 'session_id', 'user_id', 'metadata', 'user_agent',
                  'user_browser_name', 'user_browser_version', 'user_os', 'user_device', 'created_at'] as $column) {
            $this->assertArrayHasKey($column, $row, "Coluna {$column} deveria existir em blame_request");
        }

        // A existência da chave (acima) só prova o schema da tabela — um SELECT * sempre
        // retorna todas as colunas, preenchidas ou não. As asserções abaixo travam que save()
        // de fato grava o valor certo em cada uma.
        $this->assertSame('203.0.113.10', $row['ip']);
        $this->assertSame('MapasBlameTest-Agent/1.0', $row['user_agent']);
    }

    function testSavePersistsUserIdForLoggedInUser()
    {
        $this->setAppRequestIp('203.0.113.10');

        $user = $this->userDirector->createUser();
        $this->login($user);

        $request = new TestableRequest();
        $request->save();

        $row = $this->fetchBlameRequest($request->id);

        $this->assertEquals($user->id, $row['user_id']);
    }

    function testSavePersistsZeroUserIdForGuest()
    {
        $this->setAppRequestIp('203.0.113.10');

        // setUp() já faz logout(); usuário atual é GuestUser (id === 0).
        $request = new TestableRequest();
        $request->save();

        $row = $this->fetchBlameRequest($request->id);

        $this->assertEquals(0, $row['user_id']);
    }

    function testSavePersistsSessionIdMatchingTheRequestProperty()
    {
        $this->setAppRequestIp('203.0.113.10');

        $request = new TestableRequest();
        $request->save();

        $row = $this->fetchBlameRequest($request->id);

        // session_id é CHAR(32) no Postgres — valores mais curtos vêm de volta com padding
        // de espaços à direita, daí o rtrim. Comparado contra a propriedade capturada na
        // construção (não recomputado via session_id() aqui), mesmo padrão já usado para
        // browser/os/device em testSavePersistsBrowserOsDeviceFromSinergiGetters.
        $this->assertSame($request->sessionId, rtrim($row['session_id']));
    }

    function testSavePersistsMetadataAsJsonEncodedObject()
    {
        $this->setAppRequestIp('203.0.113.10');

        $request = new TestableRequest(['foo' => 'bar']);
        $request->save();

        $row = $this->fetchBlameRequest($request->id);

        $this->assertSame(json_encode($request->metadata), $row['metadata']);
    }

    function testSavePersistsBrowserOsDeviceFromSinergiGetters()
    {
        $this->setAppRequestIp('203.0.113.10');

        $chromeUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        $request = $this->withUserAgent($chromeUserAgent, function () {
            $request = new TestableRequest();
            $request->save();
            return $request;
        });

        $row = $this->fetchBlameRequest($request->id);

        $this->assertSame($request->browser->getName(), $row['user_browser_name']);
        $this->assertSame($request->browser->getVersion(), $row['user_browser_version']);
        $this->assertSame($request->os->getName(), $row['user_os']);
        $this->assertSame($request->device->getName(), $row['user_device']);
    }

    function testSavePersistsCreatedAtInExpectedFormat()
    {
        $this->setAppRequestIp('203.0.113.10');

        $request = new TestableRequest();
        $request->save();

        $row = $this->fetchBlameRequest($request->id);

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $row['created_at']);
    }

    function testSaveFlipsIsNewToFalse()
    {
        $this->setAppRequestIp('203.0.113.10');

        $request = new TestableRequest();
        $this->assertTrue($request->isNew());

        $request->save();

        $this->assertFalse($request->isNew());
    }

    function testSaveCalledDirectlyInsertsNothingIntoBlameLog()
    {
        $this->setAppRequestIp('203.0.113.10');

        $request = new TestableRequest();
        $request->save();

        $this->assertSame(0, $this->countBlameLogs($request->id));
    }

    // ===== log() =====

    function testFirstLogOnNewRequestInsertsBlameRequestAndBlameLog()
    {
        $this->setAppRequestIp('203.0.113.10');

        $request = new TestableRequest();
        $request->log('ACTION', []);

        $this->assertNotNull($this->fetchBlameRequest($request->id));
        $this->assertSame(1, $this->countBlameLogs($request->id));
    }

    function testSecondLogOnSameObjectDoesNotDuplicateBlameRequest()
    {
        $this->setAppRequestIp('203.0.113.10');

        $request = new TestableRequest();
        $request->log('ACTION 1', []);
        $request->log('ACTION 2', []);

        $this->assertSame(2, $this->countBlameLogs($request->id));

        $count = $this->app->em->getConnection()->fetchScalar(
            'SELECT count(*) FROM blame_request WHERE id = ?',
            [$request->id]
        );
        $this->assertEquals(1, $count);
    }

    function testIsNewIsFalseAfterFirstLog()
    {
        $this->setAppRequestIp('203.0.113.10');

        $request = new TestableRequest();
        $request->log('ACTION', []);

        $this->assertFalse($request->isNew());
    }

    function testNCallsToLogProduceOneRequestAndNLogs()
    {
        $this->setAppRequestIp('203.0.113.10');

        $request = new TestableRequest();
        $request->log('ACTION 1', []);
        $request->log('ACTION 2', []);
        $request->log('ACTION 3', []);

        $count = $this->app->em->getConnection()->fetchScalar(
            'SELECT count(*) FROM blame_request WHERE id = ?',
            [$request->id]
        );
        $this->assertEquals(1, $count);
        $this->assertSame(3, $this->countBlameLogs($request->id));
    }

    function testBlameLogRequestIdMatchesRequestId()
    {
        $this->setAppRequestIp('203.0.113.10');

        $request = new TestableRequest();
        $request->log('ACTION', []);

        $logs = $this->fetchBlameLogs($request->id);

        $this->assertSame($request->id, $logs[0]['request_id']);
    }

    function testActionIsPersistedVerbatim()
    {
        $this->setAppRequestIp('203.0.113.10');

        $request = new TestableRequest();
        $request->log('GET /painel/blame (blame.index)', []);

        $logs = $this->fetchBlameLogs($request->id);

        $this->assertSame('GET /painel/blame (blame.index)', $logs[0]['action']);
    }

    function testLogMetadataIsPersistedAsJsonEncodedSecondArgument()
    {
        $this->setAppRequestIp('203.0.113.10');

        $request = new TestableRequest();
        $request->log('ACTION', ['GET' => ['q' => 'busca']]);

        $logs = $this->fetchBlameLogs($request->id);

        $this->assertSame(json_encode(['GET' => ['q' => 'busca']]), $logs[0]['metadata']);
    }

    function testLogCreatedAtInExpectedFormat()
    {
        $this->setAppRequestIp('203.0.113.10');

        $request = new TestableRequest();
        $request->log('ACTION', []);

        $logs = $this->fetchBlameLogs($request->id);

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $logs[0]['created_at']);
    }

    function testLogWithEmptyMetadataArrayPersistsWithoutError()
    {
        $this->setAppRequestIp('203.0.113.10');

        $request = new TestableRequest();
        $request->log('ACTION', []);

        $logs = $this->fetchBlameLogs($request->id);

        $this->assertSame('[]', $logs[0]['metadata']);
    }

    // ===== Edge cases de dados =====

    function testMetadataWithAccentedCharactersSurvivesRoundTrip()
    {
        $this->setAppRequestIp('203.0.113.10');

        $request = new TestableRequest();
        $request->log('ACTION', ['nome' => 'inscrição do usuário']);

        $logs = $this->fetchBlameLogs($request->id);

        $this->assertSame(['nome' => 'inscrição do usuário'], json_decode($logs[0]['metadata'], true));
    }

    function testMetadataWithNestedArraysIsSerializedCorrectly()
    {
        $this->setAppRequestIp('203.0.113.10');

        $nested = ['GET' => ['filtros' => ['a', 'b'], 'paginacao' => ['page' => 2]]];

        $request = new TestableRequest();
        $request->log('ACTION', $nested);

        $logs = $this->fetchBlameLogs($request->id);

        $this->assertSame($nested, json_decode($logs[0]['metadata'], true));
    }
}
