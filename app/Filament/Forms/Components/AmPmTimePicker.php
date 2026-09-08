<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Support\Components\Contracts\HasEmbeddedView;
use Filament\Support\Enums\VerticalAlignment;

/**
 * A 12-hour AM/PM time picker that stores a 24-hour time string (H:i:s).
 *
 * Filament's built-in TimePicker only renders 24-hour numeric inputs; the
 * displayFormat('g:i A') preview never changes the actual picker UI. This
 * component renders an hour/minute/AM-PM picker instead and writes a
 * normalized "15:00:00"-style value to the underlying field state, keeping
 * the database schema (time columns) unchanged.
 */
final class AmPmTimePicker extends Field implements HasEmbeddedView
{
    public function toEmbeddedHtml(): string
    {
        $statePath = $this->getStatePath();
        $id = $this->getId();
        $isDisabled = $this->isDisabled();

        $entangle = $this->applyStateBindingModifiers("\$entangle('{$statePath}')");

        ob_start(); ?>

        <style>
            .fi-fo-ampm-time-picker{display:flex;align-items:center;width:100%;column-gap:calc(var(--spacing)*2)}
            .fi-fo-ampm-time-picker input{appearance:none;-webkit-appearance:none;-moz-appearance:none;color-scheme:light;border-style:none;font-family:inherit;font-size:var(--text-sm);line-height:var(--tw-leading,var(--text-sm--line-height));width:100%;min-width:0;padding-inline:calc(var(--spacing)*3);padding-block:calc(var(--spacing)*1.5);color:var(--gray-950);background-color:var(--color-white);border-radius:var(--radius-lg);--tw-ring-shadow:var(--tw-ring-inset,) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color,currentcolor);box-shadow:var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow);--tw-ring-color:color-mix(in oklab,var(--gray-950) 10%,transparent);--tw-ring-inset:inset;outline:none}
            .fi-fo-ampm-time-picker input:where(.dark,.dark *){color-scheme:dark;color:var(--color-white);background-color:color-mix(in oklab,var(--color-white) 5%,transparent);--tw-ring-color:color-mix(in oklab,var(--color-white) 10%,transparent)}
            .fi-fo-ampm-time-picker input:focus{--tw-ring-color:var(--primary-600)}
            .fi-fo-ampm-time-picker input:disabled{color:var(--gray-400);cursor:not-allowed;opacity:.7}
            .fi-fo-ampm-time-picker input:disabled:where(.dark,.dark *){color:var(--gray-500)}
            .fi-fo-ampm-time-picker-separator{font-size:var(--text-sm);font-weight:var(--font-weight-medium);color:var(--gray-500);flex-shrink:0}
            .fi-fo-ampm-time-picker-separator:where(.dark,.dark *){color:var(--gray-400)}
            .fi-fo-ampm-time-picker-period{display:flex;border-radius:var(--radius-lg);overflow:hidden;flex-shrink:0;--tw-ring-shadow:var(--tw-ring-inset,) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color,currentcolor);box-shadow:var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow);--tw-ring-color:color-mix(in oklab,var(--gray-950) 10%,transparent);--tw-ring-inset:inset}
            .fi-fo-ampm-time-picker-period:where(.dark,.dark *){--tw-ring-color:color-mix(in oklab,var(--color-white) 10%,transparent)}
            .fi-fo-ampm-time-picker-period button{appearance:none;border-style:none;background-color:var(--color-white);color:var(--gray-500);font-family:inherit;font-size:var(--text-xs);font-weight:var(--font-weight-medium);padding-inline:calc(var(--spacing)*3);padding-block:calc(var(--spacing)*1.5);cursor:pointer;transition:color 75ms,background-color 75ms}
            .fi-fo-ampm-time-picker-period button:where(.dark,.dark *){background-color:color-mix(in oklab,var(--color-white) 5%,transparent);color:var(--gray-400)}
            .fi-fo-ampm-time-picker-period button:hover{color:var(--gray-950)}
            .fi-fo-ampm-time-picker-period button:hover:where(.dark,.dark *){color:var(--color-white)}
            .fi-fo-ampm-time-picker-period button.fi-fo-ampm-time-picker-period-active{background-color:var(--gray-100);color:var(--gray-950)}
            .fi-fo-ampm-time-picker-period button.fi-fo-ampm-time-picker-period-active:where(.dark,.dark *){background-color:color-mix(in oklab,var(--color-white) 10%,transparent);color:var(--color-white)}
            .fi-fo-ampm-time-picker-period button:disabled{cursor:not-allowed;opacity:.7}
        </style>

        <div
            x-data="{
                state: $wire.<?= $entangle ?>,
                isDisabled: <?= $isDisabled ? 'true' : 'false' ?>,
                hour: null,
                minute: null,
                period: null,

                hydrateState() {
                    const raw = this.state;
                    const match = raw === null || raw === undefined || raw === ''
                        ? null
                        : String(raw).trim().match(/^(\d{1,2}):(\d{2})/);

                    if (! match) {
                        this.hour = null;
                        this.minute = null;
                        this.period = null;

                        return;
                    }

                    const hour24 = Number(match[1]) % 24;
                    this.period = hour24 >= 12 ? 'PM' : 'AM';
                    const hour12 = hour24 % 12;
                    this.hour = String(hour12 === 0 ? 12 : hour12);
                    this.minute = match[2];
                },

                isBlank(value) {
                    return value === null || value === undefined || value === '';
                },

                clampValue(value, min, max) {
                    if (this.isBlank(value)) {
                        return null;
                    }

                    const n = Number.parseInt(value, 10);

                    if (Number.isNaN(n)) {
                        return null;
                    }

                    return String(Math.max(min, Math.min(max, n)));
                },

                normalizeHour() {
                    this.hour = this.clampValue(this.hour, 1, 12);
                    this.pushState();
                },

                normalizeMinute() {
                    this.minute = this.clampValue(this.minute, 0, 59);

                    if (this.minute !== null) {
                        this.minute = this.minute.padStart(2, '0');
                    }

                    this.pushState();
                },

                pushState() {
                    if (this.isBlank(this.hour) || this.isBlank(this.minute) || this.isBlank(this.period)) {
                        this.state = null;

                        return;
                    }

                    const hour24 = (Number(this.hour) % 12) + (this.period === 'PM' ? 12 : 0);
                    this.state = String(hour24).padStart(2, '0') + ':' + this.minute.padStart(2, '0') + ':00';
                },
            }"
            x-init="hydrateState()"
            class="fi-fo-ampm-time-picker"
        >
            <input
                id="<?= e($id) ?>"
                type="number"
                min="1"
                max="12"
                step="1"
                inputmode="numeric"
                x-model="hour"
                x-on:input="pushState()"
                x-on:blur="normalizeHour()"
                :disabled="isDisabled"
                aria-label="<?= e(__('dashboard.resources.classes.fields.start_time_hour')) ?>"
            >

            <span class="fi-fo-ampm-time-picker-separator">:</span>

            <input
                type="number"
                min="0"
                max="59"
                step="1"
                inputmode="numeric"
                x-model="minute"
                x-on:input="pushState()"
                x-on:blur="normalizeMinute()"
                :disabled="isDisabled"
                aria-label="<?= e(__('dashboard.resources.classes.fields.start_time_minutes')) ?>"
            >

            <div class="fi-fo-ampm-time-picker-period">
                <button
                    type="button"
                    :class="period === 'AM' ? 'fi-fo-ampm-time-picker-period-active' : ''"
                    :disabled="isDisabled"
                    x-on:click="period = 'AM'; pushState()"
                >
                    AM
                </button>
                <button
                    type="button"
                    :class="period === 'PM' ? 'fi-fo-ampm-time-picker-period-active' : ''"
                    :disabled="isDisabled"
                    x-on:click="period = 'PM'; pushState()"
                >
                    PM
                </button>
            </div>
        </div>

        <?php return $this->wrapEmbeddedHtml(ob_get_clean(), inlineLabelVerticalAlignment: VerticalAlignment::Center);
    }
}
