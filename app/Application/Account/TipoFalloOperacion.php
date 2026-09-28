<?php

namespace App\Application\Account;

enum TipoFalloOperacion: string
{
    case Unauthorized = 'unauthorized';
    case OutOfScope = 'out_of_scope';
    case SameAccount = 'same_account';
    case CurrencyMismatch = 'currency_mismatch';
    case InvalidAmount = 'invalid_amount';
    case InsufficientBalance = 'insufficient_balance';
    case AccountNotFound = 'account_not_found';
    case OperationNotFound = 'operation_not_found';
    case AlreadyReversed = 'already_reversed';
    case ReasonRequired = 'reason_required';
    case IdempotencyConflict = 'idempotency_conflict';
    case InvalidOperation = 'invalid_operation';
    case KycRejected = 'kyc_rejected';
    case FraudSuspected = 'fraud_suspected';
    case TransferLimitExceeded = 'transfer_limit_exceeded';
}
