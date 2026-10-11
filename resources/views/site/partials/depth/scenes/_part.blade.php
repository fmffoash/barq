{{-- قطعة واحدة من رسمة SceneLibrary (بتنادي نفسها للمجموعات). $part: مصفوفة الوصف — شوف SceneLibrary. --}}
@php
    $C = fn ($v) => \App\Support\SceneLibrary::color($v);
    $pt = $part['t'] ?? '';
    $pAnim = $part['anim'] ?? '';
@endphp
@switch($part[0])
    @case('group')
        <div class="bq3d-part {{ $pAnim }}" style="transform: {{ $pt ?: 'none' }}; {{ $part['style'] ?? '' }}">
            @foreach ($part['parts'] as $child)
                @include('site.partials.depth.scenes._part', ['part' => $child])
            @endforeach
        </div>
        @break
    @case('box')
        <div class="bq3d-part {{ $pAnim }}" style="transform: {{ $pt ?: 'none' }};">
            @include('site.partials.depth.box', ['box' => ['class' => 'tint', 'w' => $part['w'].'rem', 'h' => $part['h'].'rem', 'd' => $part['d'].'rem', 'style' => '--c: '.$C($part['c'] ?? 'p').';'.(isset($part['face']) ? ' --face: '.$part['face'].';' : '').(isset($part['front']) ? ' --front: '.$part['front'].';' : '')]])
        </div>
        @break
    @case('cyl')
        <div class="bq3d-part {{ $pAnim }}" style="transform: {{ $pt ?: 'none' }};">
            @include('site.partials.depth.cylinder', ['cyl' => [
                'class' => isset($part['stripe']) ? 'striped' : '',
                'r' => $part['r'], 'h' => $part['h'].'rem', 'n' => $part['n'] ?? 16, 'caps' => $part['caps'] ?? true,
                'style' => '--cyl: '.$C($part['c'] ?? 'p').';'.(isset($part['cap']) ? ' --cap: '.$C($part['cap']).';' : '').(isset($part['stripe']) ? ' --stripe: '.$part['stripe'].';' : ''),
            ]])
        </div>
        @break
    @case('ball')
        @php $s = $part['s'] ?? 2; @endphp
        <span class="bq3d-ball {{ $pAnim }}" style="left: {{ -$s / 2 }}rem; top: {{ -$s / 2 }}rem; width: {{ $s }}rem; height: {{ $s }}rem; --bq3d-ball: {{ $C($part['c'] ?? 'p') }}; transform: {{ $pt ?: 'none' }};"></span>
        @break
    @case('shape')
    @case('glyph')
        @php
            $layers = $part['layers'] ?? 6;
            $step = $part['step'] ?? 0.16;
        @endphp
        <div @class(['bq3d-part bq3d-shape', 'glyph' => $part[0] === 'glyph', 'holed' => isset($part['hole']), $pAnim]) style="transform: {{ $pt ?: 'none' }}; --w: {{ $part['w'] ?? 4 }}rem; --h: {{ $part['h'] ?? 4 }}rem; --c: {{ $C($part['c'] ?? 'p') }};{{ isset($part['clip']) ? ' --clip: '.$part['clip'].';' : '' }}{{ isset($part['rad']) ? ' --rad: '.$part['rad'].';' : '' }}{{ isset($part['hole']) ? ' --hole: '.$part['hole'].';' : '' }}{{ isset($part['front']) ? ' --front: '.$part['front'].';' : '' }}{{ isset($part['fs']) ? ' --fs: '.$part['fs'].'rem;' : '' }}">
            @for ($k = 0; $k < $layers; $k++)
                <i @class(['front' => $k === $layers - 1]) style="--z: {{ round(($k - ($layers - 1) / 2) * $step, 3) }}rem; --b: {{ $k === $layers - 1 ? 1 : round(0.62 + 0.3 * $k / max(1, $layers - 1), 2) }};">{{ $part['text'] ?? '' }}</i>
            @endfor
        </div>
        @break
    @case('plane')
        <span class="bq3d-planeel {{ $pAnim }}" style="--w: {{ $part['w'] }}rem; --h: {{ $part['h'] }}rem; --c: {{ $C($part['c'] ?? 'p') }}; --rad: {{ $part['rad'] ?? '0' }}; transform: {{ $pt ?: 'none' }};"></span>
        @break
    @case('flame')
        <span class="bq3d-flame" style="left: -.45rem; top: -.75rem; transform: {{ $pt ?: 'none' }};"></span>
        @break
    @case('steam')
        <span class="bq3d-steam" style="left: -.7rem; top: -3rem; --delay: {{ $part['delay'] ?? '0s' }}; transform: {{ $pt ?: 'none' }};"></span>
        @break
@endswitch
