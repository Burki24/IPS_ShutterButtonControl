<?php

declare(strict_types=1);

class ShutterButton extends IPSModuleStrict
{
    private const DIRECTION_UP = 0;
    private const DIRECTION_DOWN = 1;

    public function Create(): void
    {
        parent::Create();

        $this->RegisterPropertyInteger('ButtonID', 0);
        $this->RegisterPropertyInteger('MoveID', 0);
        $this->RegisterPropertyInteger('PositionID', 0);
        $this->RegisterPropertyInteger('Direction', 0);
        $this->RegisterPropertyInteger('ShortPressTime', 1000);

        // Timer für LongPress
        $this->RegisterTimer(
            'LongPress',
            0,
            'IPS_RequestAction($_IPS["TARGET"], "HandleLongPress", 0);'
        );
        // interne Attribute
        $this->RegisterAttributeFloat('PressStart', 0.0);
        $this->RegisterAttributeBoolean('LongPressActive', false);

        // Debug Variablen
        $this->MaintainVariable(
            'LastDuration',
            'Letzte Druckdauer (ms)',
            VARIABLETYPE_INTEGER,
            '',
            10,
            true
        );

        $this->MaintainVariable(
            'LastAction',
            'Letzte Aktion',
            VARIABLETYPE_STRING,
            '',
            20,
            true
        );
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

        $buttonID = $this->ReadPropertyInteger('ButtonID');

        if ($buttonID > 0 && @IPS_VariableExists($buttonID)) {
            $this->RegisterMessage($buttonID, VM_UPDATE);
        }
    }

    public function MessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data): void
    {
        if ($Message === VM_UPDATE) {
            $this->HandleButton();
        }
    }

    public function RequestAction(string $Ident, mixed $Value): void
    {
        switch ($Ident) {
            case 'HandleLongPress':
                $this->HandleLongPress();
                break;
        }
        $this->SendDebug('RequestAction', $Ident, 0);
    }
    private function HandleButton(): void
    {
        $buttonID = $this->ReadPropertyInteger('ButtonID');

        if (!@IPS_VariableExists($buttonID)) {
            $this->SendDebug('Error', 'ButtonID ungültig', 0);
            return;
        }

        $value = GetValueBoolean($buttonID);

        $this->SendDebug('Button', $value ? 'Pressed' : 'Released', 0);

        if ($value === true) {
            // gedrückt
            $start = microtime(true);
            $this->WriteAttributeFloat('PressStart', $start);
            $this->WriteAttributeBoolean('LongPressActive', false);

            $this->SendDebug('Timing', 'Start: ' . $start, 0);

            // Timer starten
            $time = $this->ReadPropertyInteger('ShortPressTime');
            $this->SetTimerInterval('LongPress', $time);

            $this->SendDebug('Timer', 'LongPress Timer gestartet: ' . $time . ' ms', 0);

        } else {
            // losgelassen
            $this->SetTimerInterval('LongPress', 0);
            $this->SendDebug('Timer', 'LongPress Timer gestoppt', 0);

            $start = $this->ReadAttributeFloat('PressStart');
            $end = microtime(true);
            $duration = ($end - $start) * 1000;

            $this->SendDebug('Timing', 'Start: ' . $start . ' End: ' . $end, 0);
            $this->SendDebug('Timing', 'Dauer: ' . (string)$duration . ' ms', 0);

            // Debug Variablen
            $this->SetValue('LastDuration', (int)$duration);

            if ($duration < $this->ReadPropertyInteger('ShortPressTime')) {
                $this->SendDebug('Action', 'ShortPress erkannt', 0);
                $this->SetValue('LastAction', 'ShortPress');

                $this->HandleShortPress();
            } else {
                $this->SendDebug('Action', 'LongPress Stop erkannt', 0);
                $this->SetValue('LastAction', 'LongPress');

                $this->StopShutter();
            }
        }
    }

    public function HandleLongPress(): void
    {
        $this->SendDebug('Timer', 'LongPress ausgelöst → Bewegung starten', 0);

        $this->WriteAttributeBoolean('LongPressActive', true);

        $this->MoveShutter();
    }

    /**
     * ShortPress → feste Position
     */
    private function HandleShortPress(): void
    {
        $positionID = $this->ReadPropertyInteger('PositionID');
        $direction = $this->ReadPropertyInteger('Direction');

        if (!@IPS_VariableExists($positionID)) {
            $this->SendDebug('Error', 'PositionID ungültig', 0);
            return;
        }

        if ($direction === self::DIRECTION_UP) {
            $this->SendDebug('Shutter', 'SHORT → Position 0 (hoch)', 0);
            RequestAction($positionID, 0);
        } else {
            $this->SendDebug('Shutter', 'SHORT → Position 100 (runter)', 0);
            RequestAction($positionID, 100);
        }
    }

    /**
     * LongPress → Bewegung starten
     */
    private function MoveShutter(): void
    {
        $moveID = $this->ReadPropertyInteger('MoveID');
        $direction = $this->ReadPropertyInteger('Direction');
    
        if (!@IPS_VariableExists($moveID)) {
            $this->SendDebug('Error', 'MoveID ungültig', 0);
            return;
        }
    
        $value = ($direction === self::DIRECTION_UP) ? 'OPEN' : 'CLOSE';
    
        $this->SendDebug('Shutter', 'MOVE → ' . $value, 0);
    
        RequestAction($moveID, $value);
    }

    /**
     * Stop Bewegung
     */
    private function StopShutter(): void
    {
        $moveID = $this->ReadPropertyInteger('MoveID');
    
        if (!@IPS_VariableExists($moveID)) {
            $this->SendDebug('Error', 'MoveID ungültig (Stop)', 0);
            return;
        }
    
        $this->SendDebug('Shutter', 'STOP → STOP', 0);
    
        RequestAction($moveID, 'STOP');
    }
}
