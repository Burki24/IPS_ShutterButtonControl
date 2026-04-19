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
        $this->RegisterTimer('LongPress', 0, 'SBC_HandleLongPress($_IPS["TARGET"]);');

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

    private function HandleButton(): void
    {
        $buttonID = $this->ReadPropertyInteger('ButtonID');

        if (!@IPS_VariableExists($buttonID)) {
            return;
        }

        $value = GetValueBoolean($buttonID);

        if ($value === true) {
            // gedrückt
            $this->SendDebug('Button', 'Pressed', 0);

            $this->WriteAttributeFloat('PressStart', microtime(true));
            $this->WriteAttributeBoolean('LongPressActive', false);

            // Timer starten
            $this->SetTimerInterval('LongPress', $this->ReadPropertyInteger('ShortPressTime'));
        } else {
            // losgelassen
            $this->SendDebug('Button', 'Released', 0);

            $this->SetTimerInterval('LongPress', 0);

            $start = $this->ReadAttributeFloat('PressStart');
            $duration = (microtime(true) - $start) * 1000;

            $this->SendDebug('Duration', (string)$duration, 0);

            // Debug-Variablen setzen
            $this->SetValue('LastDuration', (int)$duration);

            if ($duration < $this->ReadPropertyInteger('ShortPressTime')) {
                // kurzer Druck
                $this->SendDebug('Action', 'ShortPress', 0);
                $this->SetValue('LastAction', 'ShortPress');

                $this->HandleShortPress();
            } else {
                // langer Druck → stoppen
                $this->SendDebug('Action', 'LongPress Stop', 0);
                $this->SetValue('LastAction', 'LongPress');

                $this->StopShutter();
            }
        }
    }

    public function HandleLongPress(): void
    {
        $this->SendDebug('LongPress', 'Start Movement', 0);

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
            return;
        }

        if ($direction === self::DIRECTION_UP) {
            RequestAction($positionID, 0);   // komplett hoch
        } else {
            RequestAction($positionID, 100); // komplett runter
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
            return;
        }

        if ($direction === self::DIRECTION_UP) {
            RequestAction($moveID, 0); // UP
        } else {
            RequestAction($moveID, 1); // DOWN
        }
    }

    /**
     * Stop Bewegung
     */
    private function StopShutter(): void
    {
        $moveID = $this->ReadPropertyInteger('MoveID');

        if (!@IPS_VariableExists($moveID)) {
            return;
        }

        RequestAction($moveID, 2); // STOP
    }
}
