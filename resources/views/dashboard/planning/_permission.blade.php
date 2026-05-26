@php
    // Centralized permission helper for planning views
    $BOARD_CAN_EDIT = $BOARD_CAN_EDIT ?? (auth()->check() && isset($board) && method_exists($board, 'canEdit') && $board->canEdit(auth()->user()));
@endphp
<script>
    // Expose a JS constant for client-side checks
    window.CAN_EDIT = {{ json_encode((bool) $BOARD_CAN_EDIT) }};
</script>
