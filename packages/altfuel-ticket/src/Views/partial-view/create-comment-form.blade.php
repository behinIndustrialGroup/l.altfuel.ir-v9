<form action="javascript:void(0)" id="{{ $form_id ?? 'comment-form' }}" enctype="multipart/form-data">
    @csrf

    <div class="card">
        <div class="row">
            <div class="col-sm-12 p-2">
                <div class="input-group mb-3 col-sm-8 float-right">
                    <button class="btn btn-success" id="play-btn" style="display: none">پخش</button>
                    <textarea name="text" id="" class="form-control" style="border: none" rows="4" placeholder="متن پیام"></textarea>
                    <div class="input-group-append">
                        {{-- <div class="input-group-text" id="voice-input" style="cursor: pointer; background:rgb(207, 1, 1); color:white; text-align: center">
                            <span class="fa fa-microphone" ></span><br>
                            <span style="writing-mode: vertical-lr;">
                                نگه دارید
                            </span>
                        </div> --}}
                    </div>
                </div>
                <div class="col-sm-4 float-left" style="font-size: 15px">
                    پیوست: فایل های مجاز {{ json_encode(config('ATConfig.attachment-file-types-translate')) }}
                    <input type="file" id="file-input" name="files[]" class="file-input">
                    <div id="inputFields"></div>
                    <button class="btn btn-info" onclick="addFn()">افزودن فایل دیگر &plus;</button>
                </div>

                {{-- <button type="button" id="record-button">شروع ضبط</button> --}}
            </div>

        </div>

    </div>

</form>
{{-- <audio id="audio-playback" controls></audio> --}}
<div class="btn btn-success" id="submit-btn" onclick="submit()">
    ثبت
</div>


<script type="text/javascript">
    var maxFileSizeInMB = parseInt('{{ config('ATConfig.max-attach-file-size') }}') / 1024;

    function checkFileSize(file) {
        var maxSizeInBytes = maxFileSizeInMB * 1024 * 1024;
        return file.size <= maxSizeInBytes;
    }

    async function compressImage(file) {
        return new Promise(function(resolve, reject) {
            const reader = new FileReader();
            reader.readAsDataURL(file);

            reader.onload = function(event) {
                const img = new Image();
                img.src = event.target.result;

                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');

                    const maxWidth = 700;
                    const scaleSize = maxWidth / img.width;
                    canvas.width = maxWidth;
                    canvas.height = img.height * scaleSize;

                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    canvas.toBlob(function(blob) {
                        resolve(blob)
                    }, 'image/jpeg', 1);
                };
            };
        })
    }

    async function submit() {

        const fileInput = $('.file-input');
        let f = new FormData($('#{{ $form_id ?? 'comment-form' }}')[0]);

        // حلقه برای بررسی فایل‌ها و فشرده‌سازی تصاویر
        for (let i = 0; i < fileInput.length; i++) {
            const inputElement = fileInput[i];
            const file = inputElement.files[0]; // دریافت فایل از input

            if (!file) continue; // اگر فایل وجود نداشت، حلقه ادامه پیدا کند

            if (!checkFileSize(file)) {
                // اگر فایل تصویری بود، حجم آن را کاهش می‌دهیم
                if (file.type.startsWith('image/')) {
                    try {
                        const convasBlob = await compressImage(file); // کاهش حجم تصویر

                        // اضافه کردن فایل فشرده‌شده به FormData
                        // f.delete('files[0]');
                        f.set(`files[${i}]`, convasBlob, file.name);
                    } catch (error) {
                        console.error('Error compressing image:', error);
                        alert('خطا در فشرده‌سازی تصویر!');
                        return; // متوقف کردن ارسال فرم در صورت بروز خطا
                    }
                } else {
                    alert(`فایل ${file.name} بیش از حد مجاز است و نمی‌تواند آپلود شود.`);
                    return; // متوقف کردن ارسال فرم در صورت عدم پذیرش فایل
                }
            }
        }

        console.log('send1');
        // f.append("nonce", window.nonce);
        send_ajax_formdata_request(
            "{{ route('ATRoutes.store') }}",
            f,
            function(response) {
                ticket = response.ticket;
                show_message(response.message)
                show_message("لطفا منتظر بمانید")
                console.log(response);
                if (typeof(show_comment_modal) === "function") {
                    show_comment_modal(ticket.id, ticket.title, ticket.user_id)
                } else {
                    window.location = "{{ route('ATRoutes.show.listForm') }}"
                }
            },
            function(data) {
                show_error(data);
                console.log(data);
            }
        )
    }

    function addFn() {
        const divEle = document.getElementById("inputFields");
        const wrapper = document.createElement("div");
        const iFeild = document.createElement("input");
        iFeild.setAttribute("type", "file");
        iFeild.setAttribute("name", "files[]");
        iFeild.classList.add("file-input");
        wrapper.appendChild(iFeild);
        divEle.appendChild(wrapper);
    }
</script>
