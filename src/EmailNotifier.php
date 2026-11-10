<?php
declare(strict_types=1);

namespace App;

final class EmailNotifier
{
    public function send(StatementRequest $request, array $report): bool
    {
        $subject = 'Банковская выписка готова';
        $body = "Ваша выписка за {$report['period']} сформирована.\n" .
            'Конечный баланс: ' . number_format($report['closingBalance'], 2, '.', ' ') . " руб.\n" .
            "Номер заявки: {$request->id}\n";
        $headers = [
            'From: ' . (getenv('MAIL_FROM') ?: 'statements@example.test'),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];
        return mail($request->email, $subject, $body, implode("\r\n", $headers));
    }
}
