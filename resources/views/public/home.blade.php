@extends('layouts.app', ['title' => 'SAPA RSBM'])

@section('content')
    @php
        $oldTanggalLahir = old('tanggal_lahir');
        $tanggalLahirDisplay = '';

        if (
            $oldTanggalLahir &&
            preg_match('/^\d{4}-\d{2}-\d{2}$/', $oldTanggalLahir)
        ) {
            try {
                $tanggalLahirDisplay = \Carbon\Carbon::createFromFormat(
                    'Y-m-d',
                    $oldTanggalLahir
                )->format('d-m-Y');
            } catch (\Throwable $e) {
                $tanggalLahirDisplay = $oldTanggalLahir;
            }
        }
    @endphp

    <style>
        .queue-check-page {
            position: relative;
            overflow: hidden;
            min-height: calc(100vh - 120px);
            padding: clamp(24px, 5vw, 64px) 0;
        }

        .queue-check-page::before,
        .queue-check-page::after {
            position: absolute;
            z-index: 0;
            width: 340px;
            height: 340px;
            border-radius: 999px;
            content: "";
            pointer-events: none;
            filter: blur(1px);
        }

        .queue-check-page::before {
            top: -180px;
            right: -130px;
            background: rgba(249, 115, 22, 0.12);
        }

        .queue-check-page::after {
            bottom: -210px;
            left: -160px;
            background: rgba(251, 146, 60, 0.1);
        }

        .queue-check-layout {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(380px, 0.85fr);
            gap: clamp(30px, 6vw, 80px);
            align-items: center;
        }

        .queue-intro {
            max-width: 680px;
        }

        .hospital-identity {
            display: inline-flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 24px;
            padding: 8px 13px 8px 9px;
            border-radius: 999px;
            background: #fff7ed;
            color: #c2410c;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .hospital-logo {
            display: grid;
            width: 30px;
            height: 30px;
            place-items: center;
            border-radius: 50%;
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: #fff;
        }

        .queue-intro h1 {
            max-width: 620px;
            margin: 0;
            color: #172033;
            font-size: clamp(38px, 5.8vw, 68px);
            font-weight: 900;
            line-height: 1.02;
            letter-spacing: -0.045em;
        }

        .queue-intro h1 span {
            color: #ea580c;
        }

        .queue-intro .lead {
            max-width: 590px;
            margin: 24px 0 0;
            color: #64748b;
            font-size: clamp(16px, 2vw, 19px);
            line-height: 1.75;
        }

        .queue-benefits {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 30px;
        }

        .benefit-item {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            padding: 9px 13px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.78);
            color: #475569;
            font-size: 13px;
            font-weight: 750;
        }

        .benefit-icon {
            display: grid;
            width: 22px;
            height: 22px;
            place-items: center;
            border-radius: 50%;
            background: #ffedd5;
            color: #ea580c;
        }

        .queue-form-card {
            position: relative;
            padding: clamp(24px, 4vw, 38px);
            border: 1px solid rgba(226, 232, 240, 0.85);
            border-radius: 30px;
            background:
                linear-gradient(
                    145deg,
                    rgba(255, 255, 255, 0.98),
                    rgba(255, 251, 247, 0.98)
                );
            box-shadow:
                0 30px 70px rgba(15, 23, 42, 0.08),
                0 8px 25px rgba(234, 88, 12, 0.05);
        }

        .queue-form-card::before {
            position: absolute;
            top: 0;
            right: 40px;
            left: 40px;
            height: 4px;
            border-radius: 0 0 10px 10px;
            background: linear-gradient(90deg, #fb923c, #ea580c);
            content: "";
        }

        .form-heading {
            margin-bottom: 28px;
        }

        .form-heading-icon {
            display: grid;
            width: 52px;
            height: 52px;
            margin-bottom: 18px;
            place-items: center;
            border-radius: 18px;
            background: #fff1e7;
            color: #ea580c;
        }

        .form-heading h2 {
            margin: 0;
            color: #172033;
            font-size: 25px;
            font-weight: 900;
            letter-spacing: -0.025em;
        }

        .form-heading p {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.65;
        }

        .queue-form {
            display: grid;
            gap: 20px;
        }

        .queue-field {
            display: grid;
            gap: 9px;
        }

        .queue-field label {
            color: #334155;
            font-size: 14px;
            font-weight: 800;
        }

        .queue-input-wrapper {
            position: relative;
        }

        .queue-input-icon {
            position: absolute;
            top: 50%;
            left: 16px;
            display: grid;
            width: 22px;
            height: 22px;
            place-items: center;
            color: #94a3b8;
            pointer-events: none;
            transform: translateY(-50%);
        }

        .queue-input {
            width: 100%;
            height: 58px;
            padding: 0 17px 0 50px;
            border: 1.5px solid #e2e8f0;
            border-radius: 17px;
            outline: none;
            background: #fff;
            color: #172033;
            font-family: inherit;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.02em;
            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease,
                background 0.2s ease;
        }

        .queue-input::placeholder {
            color: #a8b1c0;
            font-weight: 500;
        }

        .queue-input:hover {
            border-color: #cbd5e1;
        }

        .queue-input:focus {
            border-color: #f97316;
            background: #fffdfa;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.11);
        }

        .queue-field.has-error .queue-input {
            border-color: #ef4444;
            background: #fffafa;
        }

        .queue-field.has-error .queue-input:focus {
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
        }

        .field-description {
            margin: 0;
            color: #94a3b8;
            font-size: 12px;
            line-height: 1.5;
        }

        .field-error {
            display: flex;
            gap: 6px;
            align-items: flex-start;
            margin: 0;
            color: #dc2626;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.5;
        }

        .validation-alert {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 14px 16px;
            border-radius: 16px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.55;
        }

        .validation-alert svg {
            flex: 0 0 auto;
            margin-top: 1px;
        }

        .queue-submit {
            display: flex;
            width: 100%;
            min-height: 58px;
            gap: 10px;
            align-items: center;
            justify-content: center;
            margin-top: 4px;
            border: 0;
            border-radius: 17px;
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: #fff;
            cursor: pointer;
            font-family: inherit;
            font-size: 15px;
            font-weight: 850;
            box-shadow: 0 12px 25px rgba(234, 88, 12, 0.2);
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                opacity 0.2s ease;
        }

        .queue-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 30px rgba(234, 88, 12, 0.27);
        }

        .queue-submit:active {
            transform: translateY(0);
        }

        .queue-submit:disabled {
            cursor: wait;
            opacity: 0.75;
            transform: none;
        }

        .privacy-information {
            display: flex;
            gap: 8px;
            align-items: flex-start;
            justify-content: center;
            margin: 2px 0 0;
            color: #94a3b8;
            font-size: 11px;
            line-height: 1.55;
            text-align: center;
        }

        .privacy-information svg {
            flex: 0 0 auto;
            margin-top: 1px;
        }

        @media (max-width: 900px) {
            .queue-check-page {
                padding-top: 24px;
            }

            .queue-check-layout {
                grid-template-columns: 1fr;
                gap: 38px;
            }

            .queue-intro {
                max-width: 720px;
                text-align: center;
            }

            .queue-intro h1,
            .queue-intro .lead {
                margin-right: auto;
                margin-left: auto;
            }

            .hospital-identity {
                margin-right: auto;
                margin-left: auto;
            }

            .queue-benefits {
                justify-content: center;
            }

            .queue-form-card {
                width: 100%;
                max-width: 580px;
                margin: 0 auto;
            }
        }

        @media (max-width: 520px) {
            .queue-check-page {
                padding: 12px 0 32px;
            }

            .queue-intro h1 {
                font-size: 38px;
            }

            .queue-intro .lead {
                margin-top: 17px;
                font-size: 15px;
            }

            .queue-benefits {
                margin-top: 22px;
            }

            .queue-form-card {
                padding: 25px 19px;
                border-radius: 24px;
            }

            .form-heading {
                margin-bottom: 24px;
            }

            .queue-input,
            .queue-submit {
                min-height: 56px;
            }
        }
    </style>

    <section class="queue-check-page">
        <div class="queue-check-layout">
            <div class="queue-intro">
                <div class="hospital-identity">
                    <span class="hospital-logo">
                        <svg
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M12 5V19M5 12H19"
                                stroke="currentColor"
                                stroke-width="3"
                                stroke-linecap="round"
                            />
                        </svg>
                    </span>

                    SAPA RSBM
                </div>

                <h1>
                    Satu akses layanan pasien
                    <span>RS Bali Mandara.</span>
                </h1>

                <p class="lead">
                    Akses layanan pasien RS Bali Mandara dalam satu tempat.
                    Masuk menggunakan nomor rekam medis dan tanggal lahir pasien.
                </p>

                <div class="queue-benefits">
                    <div class="benefit-item">
                        <span class="benefit-icon">
                            <svg
                                width="13"
                                height="13"
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="M5 12L10 17L19 7"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </span>
                        Satu akses layanan
                    </div>

                    <div class="benefit-item">
                        <span class="benefit-icon">
                            <svg
                                width="13"
                                height="13"
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="M12 7V12L15 14"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="9"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                />
                            </svg>
                        </span>
                        Cepat dan praktis
                    </div>

                    <div class="benefit-item">
                        <span class="benefit-icon">
                            <svg
                                width="13"
                                height="13"
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="M12 3L20 7V12C20 17 16.5 20.5 12 22C7.5 20.5 4 17 4 12V7L12 3Z"
                                    stroke="currentColor"
                                    stroke-width="2.2"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </span>
                        Aman dan terintegrasi
                    </div>
                </div>
            </div>

            <div class="queue-form-card">
                <div class="form-heading">
                    <div class="form-heading-icon">
                        <svg
                            width="27"
                            height="27"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M8 7V5C8 3.9 8.9 3 10 3H14C15.1 3 16 3.9 16 5V7"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                            />
                            <rect
                                x="4"
                                y="7"
                                width="16"
                                height="14"
                                rx="3"
                                stroke="currentColor"
                                stroke-width="2"
                            />
                            <path
                                d="M9 12H15M12 9V15"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                            />
                        </svg>
                    </div>

                    <h2>Masuk ke SAPA RSBM</h2>

                    <p>
                        Masukkan data sesuai identitas pasien yang terdaftar
                        di RS Bali Mandara untuk mengakses layanan.
                    </p>
                </div>

                <form
                    id="queue-check-form"
                    class="queue-form"
                    method="POST"
                    action="{{ route('queue.check') }}"
                >
                    @csrf

                    @error('validasi')
                        <div class="validation-alert" role="alert">
                            <svg
                                width="19"
                                height="19"
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="9"
                                    stroke="currentColor"
                                    stroke-width="2"
                                />
                                <path
                                    d="M12 7V13M12 17H12.01"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                />
                            </svg>

                            <span>{{ $message }}</span>
                        </div>
                    @enderror

                    <div class="queue-field @error('rm') has-error @enderror">
                        <label for="rm">Nomor Rekam Medis</label>

                        <div class="queue-input-wrapper">
                            <span class="queue-input-icon">
                                <svg
                                    width="20"
                                    height="20"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M7 3H17C18.1 3 19 3.9 19 5V21L12 17.5L5 21V5C5 3.9 5.9 3 7 3Z"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="M9 8H15M9 12H13"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                    />
                                </svg>
                            </span>

                            <input
                                id="rm"
                                class="queue-input"
                                name="rm"
                                type="text"
                                value="{{ old('rm') }}"
                                placeholder="00.00.00"
                                inputmode="numeric"
                                autocomplete="off"
                                maxlength="8"
                                autofocus
                                required
                            >
                        </div>

                        @error('rm')
                            <p class="field-error">
                                <span>!</span>
                                <span>{{ $message }}</span>
                            </p>
                        @else
                            <p class="field-description">
                                Masukkan 6 angka. Tanda titik akan ditambahkan
                                otomatis.
                            </p>
                        @enderror
                    </div>

                    <div
                        class="queue-field @error('tanggal_lahir') has-error @enderror"
                    >
                        <label for="tanggal_lahir_display">
                            Tanggal Lahir
                        </label>

                        <div class="queue-input-wrapper">
                            <span class="queue-input-icon">
                                <svg
                                    width="20"
                                    height="20"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    aria-hidden="true"
                                >
                                    <rect
                                        x="3"
                                        y="5"
                                        width="18"
                                        height="16"
                                        rx="3"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    />
                                    <path
                                        d="M8 3V7M16 3V7M3 10H21"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                    />
                                </svg>
                            </span>

                            <input
                                id="tanggal_lahir_display"
                                class="queue-input"
                                type="text"
                                value="{{ $tanggalLahirDisplay }}"
                                placeholder="DD-MM-YYYY"
                                inputmode="numeric"
                                autocomplete="bday"
                                maxlength="10"
                                required
                            >

                            <input
                                id="tanggal_lahir"
                                name="tanggal_lahir"
                                type="hidden"
                                value="{{ old('tanggal_lahir') }}"
                            >
                        </div>

                        @error('tanggal_lahir')
                            <p class="field-error">
                                <span>!</span>
                                <span>{{ $message }}</span>
                            </p>
                        @else
                            <p class="field-description">
                                Contoh: 27-12-1968.
                            </p>
                        @enderror
                    </div>

                    <button
                        id="submit-button"
                        class="queue-submit"
                        type="submit"
                    >
                        <svg
                            width="19"
                            height="19"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <circle
                                cx="11"
                                cy="11"
                                r="7"
                                stroke="currentColor"
                                stroke-width="2.2"
                            />
                            <path
                                d="M16.5 16.5L21 21"
                                stroke="currentColor"
                                stroke-width="2.2"
                                stroke-linecap="round"
                            />
                        </svg>

                        <span>Masuk</span>
                    </button>

                    <p class="privacy-information">
                        <svg
                            width="14"
                            height="14"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <rect
                                x="5"
                                y="10"
                                width="14"
                                height="11"
                                rx="2"
                                stroke="currentColor"
                                stroke-width="2"
                            />
                            <path
                                d="M8 10V7C8 4.8 9.8 3 12 3C14.2 3 16 4.8 16 7V10"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                            />
                        </svg>

                        Data digunakan untuk mengakses layanan pasien SAPA RSBM.
                    </p>
                </form>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('queue-check-form');
            const rmInput = document.getElementById('rm');
            const dateDisplayInput = document.getElementById(
                'tanggal_lahir_display'
            );
            const dateHiddenInput = document.getElementById('tanggal_lahir');
            const submitButton = document.getElementById('submit-button');

            /**
             * Mengubah angka RM menjadi format 00.00.00.
             */
            function formatMedicalRecord(value) {
                const numbers = value.replace(/\D/g, '').slice(0, 6);
                const parts = [];

                for (let index = 0; index < numbers.length; index += 2) {
                    parts.push(numbers.slice(index, index + 2));
                }

                return parts.join('.');
            }

            /**
             * Mengubah angka tanggal menjadi format DD-MM-YYYY.
             */
            function formatBirthDate(value) {
                const numbers = value.replace(/\D/g, '').slice(0, 8);

                const day = numbers.slice(0, 2);
                const month = numbers.slice(2, 4);
                const year = numbers.slice(4, 8);

                return [day, month, year]
                    .filter(function (part) {
                        return part !== '';
                    })
                    .join('-');
            }

            /**
             * Memastikan tanggal benar dan bukan tanggal di masa depan.
             */
            function convertBirthDateToIso(value) {
                const match = value.match(
                    /^(\d{2})-(\d{2})-(\d{4})$/
                );

                if (!match) {
                    return null;
                }

                const day = Number(match[1]);
                const month = Number(match[2]);
                const year = Number(match[3]);

                const birthDate = new Date(year, month - 1, day);
                const today = new Date();

                today.setHours(0, 0, 0, 0);

                const isValidDate =
                    birthDate.getFullYear() === year &&
                    birthDate.getMonth() === month - 1 &&
                    birthDate.getDate() === day;

                if (!isValidDate || birthDate > today) {
                    return null;
                }

                return [
                    String(year).padStart(4, '0'),
                    String(month).padStart(2, '0'),
                    String(day).padStart(2, '0')
                ].join('-');
            }

            rmInput.value = formatMedicalRecord(rmInput.value);

            rmInput.addEventListener('input', function () {
                this.value = formatMedicalRecord(this.value);
                this.setCustomValidity('');
            });

            rmInput.addEventListener('blur', function () {
                const numbers = this.value.replace(/\D/g, '');

                if (numbers.length !== 6) {
                    this.setCustomValidity(
                        'Nomor rekam medis harus terdiri dari 6 angka.'
                    );
                } else {
                    this.setCustomValidity('');
                }
            });

            dateDisplayInput.addEventListener('input', function () {
                this.value = formatBirthDate(this.value);
                this.setCustomValidity('');

                const isoDate = convertBirthDateToIso(this.value);
                dateHiddenInput.value = isoDate ?? '';
            });

            dateDisplayInput.addEventListener('blur', function () {
                const isoDate = convertBirthDateToIso(this.value);

                if (!isoDate) {
                    this.setCustomValidity(
                        'Masukkan tanggal lahir yang valid dengan format DD-MM-YYYY.'
                    );

                    dateHiddenInput.value = '';
                } else {
                    this.setCustomValidity('');
                    dateHiddenInput.value = isoDate;
                }
            });

            form.addEventListener('submit', function (event) {
                rmInput.value = formatMedicalRecord(rmInput.value);

                const rmNumbers = rmInput.value.replace(/\D/g, '');
                const isoDate = convertBirthDateToIso(
                    dateDisplayInput.value
                );

                if (rmNumbers.length !== 6) {
                    rmInput.setCustomValidity(
                        'Nomor rekam medis harus terdiri dari 6 angka.'
                    );
                } else {
                    rmInput.setCustomValidity('');
                }

                if (!isoDate) {
                    dateDisplayInput.setCustomValidity(
                        'Masukkan tanggal lahir yang valid dengan format DD-MM-YYYY.'
                    );
                    dateHiddenInput.value = '';
                } else {
                    dateDisplayInput.setCustomValidity('');
                    dateHiddenInput.value = isoDate;
                }

                if (!form.checkValidity()) {
                    event.preventDefault();
                    form.reportValidity();
                    return;
                }

                submitButton.disabled = true;
                submitButton.querySelector('span').textContent =
                    'Sedang Memeriksa...';
            });
        });
    </script>
@endsection