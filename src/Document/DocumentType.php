<?php

declare(strict_types=1);

namespace Dudev\KonturOfdPhpSdk\Document;

/** Типы фискальных документов (`http/documentId.rst` — «Возможные значения типов ФД»). */
enum DocumentType: string
{
    case Receipt = 'receipt';
    case ReceiptCorrection = 'receiptCorrection';
    case Bso = 'bso';
    case BsoCorrection = 'bsoCorrection';
    case OpenShift = 'openShift';
    case CloseShift = 'closeShift';
    case CurrentStateReport = 'currentStateReport';
    case FiscalReport = 'fiscalReport';
    case FiscalReportCorrection = 'fiscalReportCorrection';
    case CloseArchive = 'closeArchive';
}
