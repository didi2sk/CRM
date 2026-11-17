<?php
declare(strict_types=1);

final class CaflouClient
{
    public function __construct(
        private string $baseUrl,
        private string $accountId,
        private string $token,
        private int $perPage = 100,
        private string $pageParam = 'page',
        private string $perPageParam = 'per_page',
        private int $delayMs = 300,
    ) {
    }

    public static function fromEnv(): self
    {
        return new self(
            rtrim(Env::get('CAFLOU_BASE_URL', 'https://app.caflou.com/api/v1'), '/'),
            Env::get('CAFLOU_ACCOUNT_ID'),
            Env::get('CAFLOU_TOKEN'),
            (int) Env::get('CAFLOU_PER_PAGE', '100'),
            Env::get('CAFLOU_PAGE_PARAM', 'page'),
            Env::get('CAFLOU_PER_PAGE_PARAM', 'per_page'),
            (int) Env::get('CAFLOU_DELAY_MS', '300'),
        );
    }

    /**
     * Generator: vracia po jednom zazname cez vsetky stranky.
     * @return Generator<array>
     */
    public int $lastPages = 0;
    public ?string $lastWarning = null;

    public function fetchAll(string $path, ?string $dataKey = null): Generator
    {
        $page = 1;
        $seen = [];
        $this->lastPages = 0;
        $this->lastWarning = null;
        while ($page <= 100000) {
            $json = $this->request($path, [
                $this->pageParam => $page,
                $this->perPageParam => $this->perPage,
            ]);
            $items = $this->extractItems($json, $path, $dataKey);
            if (!$items) {
                return;
            }
            $this->lastPages = $page;

            // Nezastavujeme podla poctu zaznamov (API moze vracat napr. pevnych 20 bez ohladu na per_page),
            // ale az ked stranka je prazdna alebo neobsahuje ziadny novy zaznam.
            $new = 0;
            foreach ($items as $item) {
                $key = isset($item['id']) ? (string) $item['id'] : md5(json_encode($item));
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $new++;
                yield $item;
            }
            if ($new === 0) {
                if ($page === 2) {
                    $this->lastWarning = "stranka 2 vratila tie iste zaznamy - API asi ignoruje parameter '{$this->pageParam}'";
                }
                return;
            }
            $page++;
        }
    }

    private function extractItems(mixed $json, string $path, ?string $dataKey): array
    {
        if (!is_array($json)) {
            return [];
        }
        if (array_is_list($json)) {
            return $json;
        }
        foreach (array_filter([$dataKey, $path, 'data', 'items', 'results']) as $k) {
            if (isset($json[$k]) && is_array($json[$k])) {
                return array_is_list($json[$k]) ? $json[$k] : [];
            }
        }
        throw new RuntimeException('Nezname rozlozenie odpovede, kluce: ' . implode(',', array_keys($json))
            . ' - nastav data_key v config/entities.php');
    }

    private function request(string $path, array $query, int $attempt = 1): mixed
    {
        $url = "{$this->baseUrl}/{$this->accountId}/{$path}?" . http_build_query($query);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Accept: application/json',
            ],
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        usleep($this->delayMs * 1000);

        if ($body === false || $code === 429 || $code >= 500) {
            if ($attempt >= 5) {
                throw new RuntimeException("Request zlyhal ($code $err): $url");
            }
            sleep(2 ** $attempt);
            return $this->request($path, $query, $attempt + 1);
        }
        if ($code >= 400) {
            throw new RuntimeException("HTTP $code pre $url: " . substr((string) $body, 0, 300));
        }
        return json_decode((string) $body, true, 512, JSON_THROW_ON_ERROR);
    }
}
