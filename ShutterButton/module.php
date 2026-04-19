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
        $this->RegisterPropertyInteger('ShutterID', 0);
        $this->RegisterPropertyInteger('Direction', 0);
        $this->RegisterPropertyInteger('ShortPressTime', 1000);

        // Timer für LongPress
        $this->RegisterTimer('LongPress', 0, 'SBC_HandleLongPress($_IPS["TARGET"]);');

        // interne Variablen
        $this->RegisterAttributeFloat('PressStart', 0.0);
        $this->RegisterAttributeBoolean('LongPressActive', false);
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

            if ($duration < $this->ReadPropertyInteger('ShortPressTime')) {
                // kurzer Druck
                $this->SendDebug('Action', 'ShortPress', 0);
                $this->HandleShortPress();
            } else {
                // langer Druck → stoppen
                $this->SendDebug('Action', 'LongPress Stop', 0);
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

    private function HandleShortPress(): void
    {
        $this->MoveShutter();
    }

    private function MoveShutter(): void
    {
        $shutterID = $this->ReadPropertyInteger('ShutterID');
        $direction = $this->ReadPropertyInteger('Direction');

        if (!@IPS_VariableExists($shutterID)) {
            return;
        }

        if ($direction === self::DIRECTION_UP) {
            RequestAction($shutterID, 0); // Hoch
        } else {
            RequestAction($shutterID, 100); // Runter
        }
    }

    private function StopShutter(): void
    {
        $shutterID = $this->ReadPropertyInteger('ShutterID');

        if (!@IPS_VariableExists($shutterID)) {
            return;
        }

        RequestAction($shutterID, -1); // STOP (abhängig vom Gerät!)
    }
}
