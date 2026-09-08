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
            .fi-fo-ampm-time-picker select{appearance:none;-webkit-appearance:none;-moz-appearance:none;color-scheme:light;border-style:none;font-family:inherit;font-size:var(--text-sm);line-height:var(--tw-leading,var(--text-sm--line-height));width:100%;padding-inline:calc(var(--spacing)*3);padding-block:calc(var(--spacing)*1.5);color:var(--gray-950);background-color:var(--color-white);background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20' stroke='%239ca3af'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right calc(var(--spacing)*3) center;border-radius:var(--radius-lg);--tw-ring-shadow:var(--tw-ring-inset,) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color,currentcolor);box-shadow:var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow);--tw-ring-color:color-mix(in oklab,var(--gray-950) 10%,transparent);--tw-ring-inset:inset;outline:none;cursor:pointer}
            .fi-fo-ampm-time-picker select:where(.dark,.dark *){color-scheme:dark;color:var(--color-white);background-color:color-mix(in oklab,var(--color-white) 5%,transparent);--tw-ring-color:color-mix(in oklab,var(--color-white) 10%,transparent)}
            .fi-fo-ampm-time-picker select option{color:var(--gray-950);background-color:var(--color-white)}
            .fi-fo-ampm-time-picker select option:where(.dark,.dark *){color:var(--color-white);background-color:var(--gray-900)}
            .fi-fo-ampm-time-picker select:focus{--tw-ring-color:var(--primary-600)}
            .fi-fo-ampm-time-picker select:disabled{color:var(--gray-400);cursor:not-allowed;opacity:.7}
            .fi-fo-ampm-time-picker select:disabled:where(.dark,.dark *){color:var(--gray-500)}
            .fi-fo-ampm-time-picker select:where(:dir(rtl),[dir=rtl],[dir=rtl] *){background-position:left calc(var(--spacing)*3) center}
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

                pushState() {
                    if (this.hour === null || this.minute === null || this.period === null) {
                        this.state = null;

                        return;
                    }

                    const hour24 = (Number(this.hour) % 12) + (this.period === 'PM' ? 12 : 0);
                    this.state = String(hour24).padStart(2, '0') + ':' + this.minute + ':00';
                },
            }"
            x-init="hydrateState()"
            class="fi-fo-ampm-time-picker"
        >
            <select
                id="<?= e($id) ?>"
                x-model="hour"
                x-on:change="pushState()"
                :disabled="isDisabled"
                aria-label="<?= e(__('dashboard.resources.classes.fields.start_time_hour')) ?>"
            >
                <option value=""></option>
                <?php for ($hour = 1; $hour <= 12; $hour++) { ?>
                    <option value="<?= $hour ?>"><?= $hour ?></option>
                <?php } ?>
            </select>

            <span class="fi-fo-ampm-time-picker-separator">:</span>

            <select
                x-model="minute"
                x-on:change="pushState()"
                :disabled="isDisabled"
                aria-label="<?= e(__('dashboard.resources.classes.fields.start_time_minutes')) ?>"
            >
                <option value=""></option>
                <?php for ($minute = 0; $minute < 60; $minute++) { ?>
                    <?php $minuteLabel = str_pad((string) $minute, 2, '0', STR_PAD_LEFT); ?>
                    <option value="<?= $minuteLabel ?>"><?= $minuteLabel ?></option>
                <?php } ?>
            </select>

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
