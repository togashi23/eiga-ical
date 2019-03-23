<?php
use Sabre\VObject;

include 'vendor/autoload.php';

class EigaIcal
{
    /**
     * 映画.comのiCal配信URL
     */
    const EIGA_ICAL_URL = 'https://eiga.com/movie/coming.ics';

    /**
     * メイン
     */
    public function exec()
    {
        // 映画.comからスケジュールを取得
        $icalData = $this->getEigaIcal();
        if ($icalData === false) {
            // スケジュールの取得に失敗した場合はエラーステータスを出力
            $this->outputError(500);
        }

        // 取得したスケジュールを終日設定に置換
        $icalAllDay = $this->replaceAllDay($icalData);

        // 置換したスケジュールを出力
        $this->outputSuccess($icalAllDay);
    }

    /**
     * iCal形式の文字列を取得
     *
     * @return string|false 映画.comから取得したiCal文字列|失敗した場合は`false`
     */
    private function getEigaIcal()
    {
        try {
            $option = [
                CURLOPT_URL => self::EIGA_ICAL_URL,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 2,
            ];

            $ch = curl_init();
            curl_setopt_array($ch, $option);
            $response = curl_exec($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            if (CURLE_OK !== $errno) {
                throw new RuntimeException('cURL Error');
            }
        } catch (RuntimeException $e) {
            return false;
        }

        return $response;
    }

    /**
     * iCal形式の文字列に存在するスケジュールを終日設定に変更
     *
     * @param string $icalData iCal軽視の文字列
     * @return string 終日設定に変更されたiCal形式の文字列
     */
    private function replaceAllDay($icalData)
    {
        $vcard = VObject\Reader::read($icalData);
        foreach ($vcard->VEVENT as $event) {
            // 開始日時
            $dtstart = new \DateTime($event->DTSTART->getValue());
            $event->DTSTART = $dtstart->format('Ymd');
            // 終了日時
            $dtend = new \DateTime($event->DTEND->getValue());
            $event->DTEND = $dtend->format('Ymd');
        }

        return $vcard->serialize();
    }

    /**
     * iCalデータを出力して終了
     *
     * @param string $data 出力データ
     */
    private function outputSuccess($data)
    {
        http_response_code(200);
        header('Content-Type: text/calendar; charset=utf-8');
        echo $data;
        exit;
    }

    /**
     * エラーステータスを出力して終了
     *
     * @param int $status ステータスコード
     */
    private function outputError($status = 500)
    {
        http_response_code($status);
        echo 'Error.';
        exit;
    }
}

$eiga = new EigaIcal;
$eiga->exec();
