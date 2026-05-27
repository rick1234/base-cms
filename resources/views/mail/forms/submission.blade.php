<p>{{ $body }}</p>

@if ($answers !== [])
    <table>
        <tbody>
            @foreach ($answers as $label => $value)
                <tr>
                    <th scope="row">{{ $label }}</th>
                    <td>{{ $value }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
