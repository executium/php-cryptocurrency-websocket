# PHP Cryptocurrency Websocket
![crypto websocket](https://i.imgur.com/VGeP4EG.png)
This PHP cryptocurrency websocket connects you to the executium cryptocurrency websocket network. This repository can be used for an array of projects where you require a live market feed.

## How to install
1. Clone this repository

```
git clone  https://github.com/executium/php-cryptocurrency-websocket.git  
```

2. The `cd` into the directory and install the dependencies using composer. 
```
cd php-cryptocurrency-websocket/
composer install
```

3. Then finally run the script
```
php index.php
```

Finally, you should see an output similar to the following:
```
GET request to https://wss-public.executium.com:2083/socket.io/?EIO=3&transport=polling
Cookie: __cfduid=d344885355ebe04675bc7d57b5f84ad8e1597399433; io=YUMWpgTk5CwoXI7fAAKr
trying decode: 96:0{"sid":"YUMWpgTk5CwoXI7fAAKr","upgrades":["websocket"],"pingInterval":25000,"pingTimeout":5000}2:40
trying encode: ["message",{"req":"binance","s":"btcusdt","o":"asks-binance-btcusdt"}]
POST request to https://wss-public.executium.com:2083/socket.io/?EIO=3&transport=polling&sid=YUMWpgTk5CwoXI7fAAKr
GET request to https://wss-public.executium.com:2083/socket.io/?EIO=3&transport=polling&sid=YUMWpgTk5CwoXI7fAAKr
trying decode: 91:42["obreq",{"n":"8e8e16d7.executium.net","s":"binance-btcusdt","o":"asks-binance-btcusdt"}]
GET request to https://8e8e16d7.executium.net:2083/socket.io/?EIO=3&transport=polling
trying decode: 96:0{"sid":"N6AXZMacovQWFgsXAApl","upgrades":["websocket"],"pingInterval":25000,"pingTimeout":5000}2:40
trying encode: ["message",{"subscribe":"asks\/binance-btcusdt-1"}]
POST request to https://8e8e16d7.executium.net:2083/socket.io/?EIO=3&transport=polling&sid=N6AXZMacovQWFgsXAApl
n: asks/binance-btcusdt-1, price: 11694.23, quantity: 2.411781, time: 1597399435591
n: asks/binance-btcusdt-1, price: 11693.5, quantity: 2.794828, time: 1597399436682
n: asks/binance-btcusdt-1, price: 11693.5, quantity: 2.734814, time: 1597399437675
n: asks/binance-btcusdt-1, price: 11693.5, quantity: 2.704761, time: 1597399438632
n: asks/binance-btcusdt-1, price: 11693.39, quantity: 3.107546, time: 1597399439674
n: asks/binance-btcusdt-1, price: 11693.39, quantity: 2.955887, time: 1597399440660
```


NOTE: This script uses `php-curl`, to install use the following command
```
apt install php-curl
```

## How to use
The folling variables are highlighted for easy customization.

```php
$port = 2083;
$host = 'wss-public.executium.com';
$side = "asks";
$symbol = "btcusdt";
$exchange = "binance";
$orderbook_level=1;
```

## Host and Port
As default the host for the cryptocurrency websocket is `wss-public.executium.com` on port `2083`. Cloudflare is utilized for protection.

## Side
You can select `bids` or `asks` to indicate what side of the orderboook you want back.

## Symbol
You can select from a range of symbols, on every exchange supported, executium will list all `tradable` symbols.

## Exchange
Over `25` exchanges are supported by executium, the list and code is as follows:

Exchange | Executium Code |Active | Symbols Count
------------ | ------------ | ------------ | ------------
Binance|binance|Yes|677
Bitfinex|bitfinex|Yes|310
Bitflyer|bitflyer|Yes|11
Bithumb|bithumb|Yes|106
Bitmart|bitmart|Yes|5
Bitmex|bitmex|Yes|15
Bitstamp|bitstamp|Yes|32
Bittrex|bittrex|Yes|490
Bybit|bybit|Yes|5
Coinbase|coinbase|Yes|169
Coinbasepro|coinbasepro|Yes|78
Coincheck|coincheck|Yes|1
Deribit|deribit|Yes|8
Ftx|ftx|Yes|368
Gateio|gateio|Yes|484
Hbdm|hbdm|Yes|44
Huobipro|huobipro|Yes|596
Indodax|indodax|Yes|68
Itbit|itbit|Yes|6
Kraken|kraken|Yes|155
Krakenfutures|krakenfutures|Yes|19
Kucoin|kucoin|Yes|458
Liquid|liquid|Yes|157
Okex|okex|Yes|400
Poloniex|poloniex|Yes|215
Upbit|upbit|Yes|263
Zb|zb|Yes|154

## Orderbook level
Executium supports level 1 to 10 of the orderbook, set the `$orderbook_level` to correspond to the orderbook level you want to return.

## How to find out what is supported?
You can find out what is supported by using the `symbol` endpoint as follows:

```
https://marketdata.executium.com/api/v2/system/symbols
```

If you want to break it down to exchange then you can add a `GET` parameter as follows:

```
https://marketdata.executium.com/api/v2/system/symbols?exchange=bitfinex
```

## Dealing with the output
If you are looking to capture and manipulate the output then the following passage is recommended:

```php
function orderbook_pushed($match)
{
    $data = json_decode($match[2], true);

    if($data[0] == 'dp')
    {
        $d = explode(",", trim($data[1]['d'], "[]"));
        $price = $d[0];
        $quantity = $d[1];
        $ago = $d[2];
        echo "n: {$data[1]['n']}, price: {$price}, quantity: {$quantity}, time: {$ago}" . PHP_EOL;
    }
}

```

When `dp` is passed this will contain the desired data. For your developments it is recommended this is the area in which you modify. Within the `orderbook_pushed()` function you can manipulate the output as you see fit.

## Online Example
You can view an online example of the javascript websocket at [https://marketdata.executium.com/realtime-cryptocurrency-market-prices-websockets/](https://marketdata.executium.com/realtime-cryptocurrency-market-prices-websockets/). The code related to the javascript can be found [in this repository](https://github.com/executium/real-time-cryptocurrency-market-prices-websocket).

## License related to code

MIT License

Copyright (c) 2020 executium ltd

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
