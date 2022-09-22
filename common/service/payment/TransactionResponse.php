<?php

namespace common\service\payment;

class TransactionResponse
{
    private ?string $transactionId = null;
    private ?string $redirectUrl = null;
    private array $details = [];

    /**
     * @param array<mixed> $details
     */
    public function __construct(?string $transactionId, ?string $redirectUrl, array $details)
    {
        $this->transactionId = $transactionId;
        $this->redirectUrl = $redirectUrl;
        $this->details = $details;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    /**
     * @return mixed[]
     */
    public function getDetails(): array
    {
        return $this->details;
    }
}