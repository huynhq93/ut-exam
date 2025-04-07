# Test Cases Checklist

## OrderProcessingService

### Common
- [x] processOrders returns list of processed orders
- [x] processOrders returns false on general exception

### Trường hợp không có đơn hàng
- [x] processOrders handles empty order list

### Trường hợp đơn hàng có type là A
- [x] processOrders handles type A orders with successful CSV export
- [x] processOrders handles type A orders with CSV export failure
- [x] processOrders handles type A orders with high value
- [x] processOrders handles type A orders with low value

### Trường hợp đơn hàng có type là B
- [x] processOrders handles type B orders with API error
- [x] processOrders handles type B orders with successful API response
- [x] processOrders handles type B orders with successful API response and pending status
- [x] processOrders handles type B orders with successful API response and error status
- [x] processOrders handles type B orders with successful API response but status of API response is error
- [x] processOrders handles type B orders with high value
- [x] processOrders handles type B orders with low value

### Trường hợp đơn hàng có type là C
- [x] processOrders handles type C orders with completed status
- [x] processOrders handles type C orders with in_progress status
- [x] processOrders handles type C orders with high value
- [x] processOrders handles type C orders with low value

### Trường hợp đơn hàng có type là C
- [x] processOrders handles unknown order type
- [x] processOrders handles type C orders with in_progress status
- [x] processOrders handles unknown order type with high value
- [x] processOrders handles unknown order type with low value

### Kiểm tra cập nhật trạng thái đơn hàng vào DB
- [x] processOrders updates order priority based on amount
- [x] processOrders handles database exception