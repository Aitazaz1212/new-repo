create table Time
(
    getOrderTime       varchar(100) null,
    backupTime         varchar(100) null,
    getActiveOrderTime varchar(100) null
)
    charset = latin1;

create table about_companies
(
    id                    bigint unsigned auto_increment
        primary key,
    global_id             varchar(255) null,
    account_name          varchar(255) null,
    account_logo          varchar(255) null,
    street_address        varchar(255) null,
    city                  varchar(255) null,
    state                 varchar(255) null,
    country               varchar(255) null,
    postcode              varchar(255) null,
    ph                    varchar(255) null,
    email                 varchar(255) null,
    contact_person        varchar(255) null,
    contact_position      text         null,
    contact_details       json         null,
    created_at            timestamp    null,
    updated_at            timestamp    null,
    street_address_line_2 varchar(255) null
)
    collate = utf8mb4_unicode_ci;

create table address
(
    id                int auto_increment
        primary key,
    id_customer       int          null,
    id_address_type   int          null,
    id_address_status int          null,
    name              varchar(128) null,
    address_line_1    varchar(128) null,
    address_line_2    varchar(128) null,
    address_line_3    varchar(128) null,
    address_line_4    varchar(128) null,
    address_line_5    varchar(128) null,
    postal_reference  varchar(20)  null,
    country_code      varchar(4)   null,
    longitude         varchar(32)  null,
    latitude          varchar(32)  null
)
    charset = latin1;

create table address_status
(
    id             int         not null
        primary key,
    address_status varchar(32) null
)
    charset = latin1;

create table address_type
(
    id           int         not null
        primary key,
    address_type varchar(32) null
)
    charset = latin1;

create table agents
(
    id   int          not null
        primary key,
    name varchar(400) null,
    paid double       null,
    owed double       null
)
    engine = MyISAM
    charset = utf8mb3;

create table allowed_ips
(
    id          int         not null
        primary key,
    address     varchar(48) null,
    description text        null,
    user_id     int         not null,
    created_at  timestamp   null,
    updated_at  timestamp   null
)
    charset = latin1;

create table allowed_ips_backup
(
    id          int         not null
        primary key,
    address     varchar(48) null,
    description text        null,
    user_id     int         not null,
    created_at  timestamp   null,
    updated_at  timestamp   null
)
    charset = latin1;

create table amazonStock
(
    itemCode varchar(200) null
)
    charset = latin1;

create table api_keys
(
    id         bigint unsigned auto_increment
        primary key,
    name       varchar(255) null,
    `key`      text         null,
    created_at timestamp    null,
    updated_at timestamp    null
)
    collate = utf8mb4_unicode_ci;

create table archiveOrders
(
    id             int                                    not null
        primary key,
    cid            int                                    null,
    orderStatus    varchar(100)                           null,
    startDate      varchar(50)                            null,
    startTime      varchar(50)                            null,
    endDate        varchar(50)                            null,
    endTime        varchar(50)                            null,
    staffName      varchar(50)                            null,
    staffid        int                                    null,
    paid           double       default 0                 null,
    total          double       default 0                 null,
    lastMaintainer varchar(100) default ''                null,
    lastMaintainID int                                    null,
    delRoute       varchar(200)                           null,
    dispatchType   varchar(100) default 'Delivery'        null,
    collector      varchar(200)                           null,
    colDelDate     varchar(100)                           null,
    carrier        varchar(200)                           null,
    orderType      varchar(100)                           null,
    direct         int                                    null,
    paymentType    varchar(200)                           null,
    delOrder       int                                    null,
    authNo         varchar(100)                           null,
    cardNo         varchar(4)                             null,
    exp            varchar(20)                            null,
    companyName    varchar(200)                           null,
    ouref          varchar(400)                           null,
    time_stamp     timestamp    default CURRENT_TIMESTAMP not null,
    proforma       int          default 0                 not null,
    showVat        int          default 0                 null,
    showNotes      int          default 1                 null,
    serType        varchar(100) default 'sale'            null,
    icType         varchar(100) default 'invoice'         null,
    iNo            varchar(100)                           null,
    ebayID         varchar(400)                           null,
    vatAmount      double                                 null,
    zeroVat        int          default 0                 null,
    ebayStatus     varchar(100)                           null,
    deliverBy      varchar(100) default '0'               not null,
    tx             int          default 0                 not null,
    delTime        varchar(20)                            null,
    delStat        varchar(20)                            null,
    agent          varchar(400)                           null,
    agentPaid      int          default 0                 null,
    agentOwed      double       default 0                 null,
    rate           double       default 0                 null,
    txt            int          default 0                 null,
    code           varchar(200)                           null,
    parcelid       varchar(20)                            null,
    neighbour      varchar(400) default '0'               null,
    aftersales     int          default 0                 null,
    safePlace      varchar(200) default '0'               null,
    done           int          default 0                 null,
    ebay           varchar(100) default '0'               null,
    notLoaded      int          default 0                 null,
    p              int          default 0                 null,
    wes            int          default 0                 null,
    cStatus        varchar(100) default 'none'            null,
    colOrder       int          default 0                 null,
    colTime        varchar(10)                            null,
    colRoute       varchar(100)                           null,
    extra          varchar(10)                            null,
    beds           varchar(255) default '0'               null,
    delDate        varchar(20)                            null,
    colID          int          default 0                 null,
    delID          int          default 0                 null,
    lat            varchar(100)                           null,
    lng            varchar(100)                           null,
    amazonID       varchar(100)                           null,
    amzShipped     int          default 0                 null,
    refund         int          default 0                 null,
    postLoc        varchar(50)  default '0'               null,
    post           int          default 0                 null,
    sender         varchar(200) default '0'               null,
    stamp          varchar(100)                           null,
    startStamp     varchar(100) default '0'               null,
    wholeError     int          default 0                 null
)
    engine = MyISAM
    charset = utf8mb3;

create index cid
    on archiveOrders (cid);

create table barcodes
(
    oid  int          null,
    code varchar(400) null
)
    charset = latin1;

create table blockDays
(
    id         int          not null
        primary key,
    date       varchar(100) null,
    saturday   date         null,
    user_id    int          null,
    created_at timestamp    null,
    updated_at timestamp    null
)
    charset = latin1;

create table breaks
(
    id                        bigint unsigned not null
        primary key,
    break_type                varchar(255)    null,
    break_duration            varchar(255)    null,
    driving_time_before_break varchar(255)    null,
    working_time_before_break varchar(255)    null,
    allowed_break_shift       varchar(255)    null,
    created_at                timestamp       null,
    updated_at                timestamp       null
)
    collate = utf8mb4_unicode_ci;

create table bulletin
(
    id       int                  not null
        primary key,
    expires  timestamp            null,
    message  text                 null,
    staff_id int                  null,
    title    varchar(255)         null,
    deleted  tinyint(1) default 0 null
)
    charset = latin1;

create table bulletins
(
    id         int unsigned not null
        primary key,
    expires    timestamp    null,
    deleted_at timestamp    null,
    user_id    int          not null,
    message    text         not null,
    title      varchar(255) not null,
    created_at timestamp    null,
    updated_at timestamp    null
)
    collate = utf8mb3_unicode_ci;

create table camp
(
    id       int          not null
        primary key,
    name     varchar(400) null,
    sent     int          null,
    not_sent int          null,
    date     varchar(100) null
)
    charset = latin1;

create table cancelled_orders
(
    id                int                          default 0                 not null,
    id_company        int                          default 0                 null,
    id_status         int                          default 1                 null comment 'This should be ‘id_order_status’; the values are form the ‘order_status’ table',
    cid               int                                                    null,
    delRoute          varchar(32) charset utf8mb3                            null,
    endDate           varchar(32) charset utf8mb3                            null,
    endTime           varchar(32) charset utf8mb3                            null,
    lastMaintainer    varchar(48) charset utf8mb3  default ''                null,
    lastMaintainID    int                                                    null,
    orderStatus       varchar(24) charset utf8mb3                            null,
    paid              double                       default 0                 null,
    startDate         varchar(24) charset utf8mb3                            null,
    startTime         varchar(24) charset utf8mb3                            null,
    staffName         varchar(48) charset utf8mb3                            null,
    staffid           int                                                    null,
    total             double                       default 0                 null,
    dispatchType      varchar(32) charset utf8mb3  default 'Delivery'        null,
    collector         varchar(32) charset utf8mb3                            null,
    colDelDate        varchar(24) charset utf8mb3                            null,
    carrier           varchar(32) charset utf8mb3                            null,
    orderType         varchar(32) charset utf8mb3                            null,
    direct            int                                                    null,
    paymentType       varchar(32) charset utf8mb3                            null,
    delOrder          int                                                    null,
    authNo            varchar(32) charset utf8mb3                            null,
    cardNo            varchar(4) charset utf8mb3                             null,
    exp               varchar(20) charset utf8mb3                            null,
    companyName       varchar(200) charset utf8mb3                           null,
    ouref             varchar(400) charset utf8mb3                           null,
    time_stamp        timestamp                    default CURRENT_TIMESTAMP not null,
    proforma          int                          default 0                 not null,
    showVat           int                          default 0                 null,
    showNotes         int                          default 1                 null,
    serType           varchar(100) charset utf8mb3 default 'sale'            null,
    icType            varchar(100) charset utf8mb3 default 'invoice'         null,
    iNo               varchar(100) charset utf8mb3                           null,
    ebayID            varchar(64) charset utf8mb3                            null,
    vatAmount         double                                                 null,
    zeroVat           int                          default 0                 null,
    ebayStatus        varchar(100) charset utf8mb3                           null,
    deliverBy         varchar(100) charset utf8mb3 default '0'               not null,
    tx                int                          default 0                 not null,
    delTime           varchar(20) charset utf8mb3                            null,
    delStat           varchar(20) charset utf8mb3                            null,
    agent             text charset utf8mb3                                   null,
    agentPaid         int                          default 0                 null,
    agentOwed         double                       default 0                 null,
    rate              double                       default 0                 null,
    txt               int                          default 0                 null,
    code              varchar(200) charset utf8mb3                           null,
    parcelid          varchar(20) charset utf8mb3                            null,
    neighbour         varchar(400) charset utf8mb3 default '0'               null,
    aftersales        int                          default 0                 null,
    safePlace         varchar(200) charset utf8mb3 default '0'               null,
    done              int                          default 0                 null,
    ebay              varchar(100) charset utf8mb3 default '0'               null,
    notLoaded         int                          default 0                 null,
    p                 int                          default 0                 null,
    wes               int                          default 0                 null,
    cStatus           varchar(100) charset utf8mb3 default 'none'            null,
    colOrder          int                          default 0                 null,
    colTime           varchar(10) charset utf8mb3                            null,
    colRoute          varchar(100) charset utf8mb3                           null,
    extra             varchar(10) charset utf8mb3                            null,
    beds              varchar(255) charset utf8mb3 default '0'               null,
    delDate           varchar(20) charset utf8mb3                            null,
    colID             int                          default 0                 null,
    delID             int                          default 0                 null,
    lat               varchar(100) charset utf8mb3                           null,
    lng               varchar(100) charset utf8mb3                           null,
    amazonID          varchar(100) charset utf8mb3                           null,
    amzShipped        int                          default 0                 null,
    refund            int                          default 0                 null,
    postLoc           varchar(50) charset utf8mb3  default '0'               null,
    post              int                          default 0                 null,
    sender            varchar(200) charset utf8mb3 default '0'               null,
    stamp             varchar(100) charset utf8mb3                           null,
    startStamp        varchar(100) charset utf8mb3 default '0'               null,
    wholeError        int                          default 0                 null,
    mfError           int                          default 0                 null,
    feedbackScore     int                          default 0                 null,
    delissue          int                          default 0                 null,
    defects           int                          default 0                 null,
    g_id              varchar(100) charset utf8mb3 default '0'               null,
    g_num             varchar(100) charset utf8mb3 default '0'               null,
    g_desc            varchar(128) charset utf8mb3 default '0'               null,
    g_ship            varchar(24) charset utf8mb3  default '0'               null,
    oos               int                          default 0                 null,
    mgmt              int                          default 0                 null,
    processed         int                          default 0                 null,
    paidBeds          int                          default 0                 null,
    pTime             varchar(16) charset utf8mb3  default '0'               null,
    refundBox         int                          default 0                 null,
    id_delivery_route int                          default 0                 null,
    id_order_type     int                          default 0                 null,
    id_payment_type   int                          default 0                 null,
    hold              int                          default 0                 null,
    missed            int                          default 0                 null,
    urgent            int                          default 0                 null,
    retention         int                          default 0                 null,
    paypal            int                          default 0                 null,
    reopen            int                          default 0                 null,
    id_supplier       int                          default 0                 null,
    paid_supplier     int                          default 0                 null,
    invoice_made      int                          default 0                 null,
    id_seller         int                          default 0                 null,
    seller_paid       int                          default 0                 null,
    discount_amount   decimal(9, 2)                default 0.00              null,
    discount_rate     decimal(6, 2)                default 0.00              null comment 'expressed as a percentage',
    vat_paid          decimal(6, 2)                default 0.00              null comment 'expressed as a percentage',
    paypal_payment    text charset utf8mb3                                   null,
    last_trans_id     varchar(50) charset utf8mb3  default '0'               null,
    to_collect        int                          default 0                 null,
    deleted_by        varchar(50)                                            null,
    delete_time       timestamp                    default CURRENT_TIMESTAMP not null,
    is_split          int                          default 0                 null,
    paid_courier      int                          default 0                 null,
    is_collected      int                          default 0                 null,
    escalate          int                          default 0                 null,
    ordered           int                          default 0                 null,
    merch_ref         varchar(50)                                            null,
    trust_pilot       int                          default 0                 null,
    stolen            int                          default 0                 null,
    sales_record      varchar(10)                  default '0'               null
)
    charset = latin1;

create table capacity_units
(
    id                             bigint unsigned not null
        primary key,
    capacity_unit                  varchar(255)    null,
    capacity_decimal_precision     varchar(255)    null,
    capacity_label                 varchar(255)    null,
    volume_units                   varchar(255)    null,
    volume_label                   varchar(255)    null,
    volume_decimal_precision       varchar(255)    null,
    enable_capacity_constraint_one tinyint(1)      null,
    enable_capacity_constraint_two tinyint(1)      null,
    created_at                     timestamp       null,
    updated_at                     timestamp       null
)
    collate = utf8mb4_unicode_ci;

create table categories
(
    id              int                  not null
        primary key,
    always_in_stock tinyint(1) default 0 null,
    id_staff        int                  null,
    id_supplier     int        default 0 null,
    description     text                 null,
    name            varchar(200)         null,
    staff           varchar(200)         null
)
    charset = latin1;

create table chats
(
    id     int           not null
        primary key,
    sender varchar(100)  null,
    rcvr   varchar(100)  null,
    date   varchar(100)  null,
    msg    varchar(1000) null,
    seen   int default 0 null
)
    charset = latin1;

create table clicks
(
    camp int          null,
    link varchar(400) null,
    time varchar(100) null,
    id   int          null
)
    charset = latin1;

create table codes
(
    lineid    int           null,
    bar       varchar(48)   null,
    oid       int           null,
    parcelid  int           null,
    itemCode  varchar(128)  null,
    loaded    int default 0 null,
    warehouse int default 0 null,
    complete  int default 0 null,
    collected int default 0 null,
    pk        int           not null
        primary key,
    boxes     int           null comment 'for boxes logics'
)
    charset = latin1;

create index idx_codes_oid
    on codes (oid);

create index index_bar
    on codes (bar);

create table col
(
    oid   int           null,
    date  varchar(20)   null,
    route varchar(100)  null,
    num   int default 0 null
)
    charset = latin1;

create table colRoute
(
    route     varchar(100)                  null,
    date      varchar(20)                   null,
    id        int                           not null
        primary key,
    driver    varchar(100)                  null,
    staff     varchar(100)                  null,
    status    varchar(100) default 'Routed' null,
    mate      varchar(100)                  null,
    vehicle   varchar(100)                  null,
    payments  double       default 0        null,
    toCollect double       default 0        null
)
    charset = latin1;

create table company
(
    id           int           not null
        primary key,
    company_name varchar(128)  null,
    id_email     int default 0 null,
    l_id_email   int default 0 null,
    importer_id  int unsigned  not null,
    created_at   timestamp     null,
    updated_at   timestamp     null
)
    charset = latin1;

create table configurations
(
    id         bigint unsigned auto_increment
        primary key,
    `key`      varchar(255)                                              not null,
    type       enum ('ebay', 'cox-and-cox', 'general') default 'general' not null,
    value      text                                                      null,
    order_by   int                                                       null,
    details    text                                                      null,
    created_at timestamp                                                 null,
    updated_at timestamp                                                 null,
    constraint configurations_key_unique
        unique (`key`)
)
    collate = utf8mb4_unicode_ci;

create table cons
(
    id        int                             not null
        primary key,
    consid    varchar(20)                     null,
    deliverTo varchar(200)                    null,
    town      varchar(200)                    null,
    postcode  varchar(20)                     null,
    pieces    int                             null,
    weight    double                          null,
    type      varchar(200) default 'Delivery' null,
    date      varchar(40)                     null,
    status    varchar(100)                    null,
    stamp     varchar(40)                     null,
    `to`      int                             null,
    sender    int                             null,
    parcelid  int                             null,
    price     double       default 0          null,
    paid      double       default 0          null,
    oid       int                             null,
    delDate   varchar(20)                     null,
    colID     int                             null,
    hide      int          default 0          null
)
    charset = latin1;

create table containerFiles
(
    id          int          not null
        primary key,
    name        varchar(200) null,
    containerID varchar(400) null
)
    charset = latin1;

create table containers
(
    id          int           not null
        primary key,
    loaded      varchar(20)   null,
    eta         varchar(20)   null,
    containerID varchar(400)  null,
    rxd         int default 0 null,
    items       text          null,
    staff_id    int           null,
    created_at  datetime      null,
    updated_at  datetime      null
)
    charset = latin1;

create table coordinates
(
    id     int          not null
        primary key,
    pc     varchar(10)  null,
    lat    varchar(30)  null,
    lng    varchar(30)  null,
    street varchar(100) null,
    town   varchar(50)  null
)
    charset = latin1;

create index index_postcode
    on coordinates (pc);

create table count
(
    num            int null,
    parcel_id_seed int null,
    orders_count   int null,
    refund         int null,
    post           int null,
    exchange       int null
)
    charset = latin1;

create table counter
(
    num int default 0 null
)
    charset = latin1;

create table credit_notes
(
    id         int                         not null
        primary key,
    amount     decimal(11, 2)              not null,
    order_id   int                         not null,
    seller_id  int                         not null,
    user_id    int                         not null,
    created_at timestamp                   null,
    updated_at timestamp                   null,
    used       decimal(11, 2) default 0.00 null
)
    charset = latin1;

create table cron_tasks
(
    id          int           not null
        primary key,
    name        varchar(45)   null,
    description varchar(150)  null,
    value       int default 0 null,
    log         varchar(200)  null
)
    charset = latin1;

create table customer_delivery_package
(
    id                bigint unsigned auto_increment
        primary key,
    oid               varchar(255)                   not null,
    package_id        bigint unsigned                not null,
    sub_package_id    bigint unsigned                null,
    amount            decimal(10, 2)                 not null,
    paid              tinyint(1)   default 0         not null,
    stripe_payment_id varchar(255)                   null,
    currency          varchar(3)   default 'GBP'     not null,
    payment_status    varchar(255) default 'pending' not null,
    user_id           bigint unsigned                null,
    created_at        timestamp                      null,
    updated_at        timestamp                      null
)
    collate = utf8mb4_unicode_ci;

create index customer_delivery_package_oid_index
    on customer_delivery_package (oid);

create index customer_delivery_package_package_id_index
    on customer_delivery_package (package_id);

create index customer_delivery_package_sub_package_id_index
    on customer_delivery_package (sub_package_id);

create index customer_delivery_package_user_id_index
    on customer_delivery_package (user_id);

create table customer_location_time_windows
(
    id                   bigint unsigned auto_increment
        primary key,
    customer_location_id varchar(255) null,
    day                  varchar(255) null,
    start_time           varchar(255) null,
    end_time             varchar(255) null,
    created_at           timestamp    null,
    updated_at           timestamp    null
)
    collate = utf8mb4_unicode_ci;

create table customer_locations
(
    id                                    bigint unsigned not null
        primary key,
    user_id                               varchar(255)    null,
    name                                  varchar(255)    null,
    customer_location_reference           varchar(255)    null,
    description                           varchar(255)    null,
    client_name                           varchar(255)    null,
    primary_telephone_number              varchar(255)    null,
    secondary_telephone_number            varchar(255)    null,
    email                                 varchar(255)    null,
    website                               varchar(255)    null,
    rate                                  varchar(255)    null,
    address                               varchar(255)    null,
    postcode                              varchar(255)    null,
    run_distance_limit                    varchar(255)    null,
    location_is_verified                  varchar(255)    null,
    allow_notifications_by                varchar(255)    null,
    preferred_drivers                     varchar(255)    null,
    vehicle_requirements                  varchar(255)    null,
    fixed_time_per_address                varchar(255)    null,
    fixed_time_per_order                  varchar(255)    null,
    variable_time_per_capacity_delivery   varchar(255)    null,
    variable_time_per_capacity_collection varchar(255)    null,
    created_at                            timestamp       null,
    updated_at                            timestamp       null
)
    collate = utf8mb4_unicode_ci;

create table customer_type
(
    id            int         not null
        primary key,
    customer_type varchar(32) null
)
    charset = latin1;

create table customers
(
    id                   int auto_increment
        primary key,
    accountNo            varchar(100)                           null,
    agent                int          default 0                 null,
    businessName         varchar(255)                           null,
    email1               varchar(255)                           null,
    email2               varchar(255)                           null,
    email3               varchar(255)                           null,
    fax                  text                                   null,
    mob                  text                                   null,
    number               text                                   null,
    web                  varchar(200)                           null,
    street               varchar(255)                           null,
    tel                  varchar(100)                           null,
    town                 varchar(255)                           null,
    postcode             varchar(20)                            null,
    dBusinessName        varchar(255)                           null,
    dNumber              varchar(255)                           null,
    dStreet              varchar(255)                           null,
    dTown                varchar(255)                           null,
    dPostcode            varchar(20)                            null,
    discount             double       default 0                 null,
    id_customer_type     int                                    null,
    id_seller            int                                    null,
    isAccount            varchar(11)  default '0'               null,
    paymentType          varchar(200)                           null,
    type                 varchar(20)                            null,
    owes                 double       default 0                 null,
    userID               varchar(200)                           null,
    company              varchar(255) default '0'               null,
    mail                 int          default 1                 null,
    timestamp            timestamp    default CURRENT_TIMESTAMP null,
    numerical_pc         bigint       default 0                 null,
    rate                 varchar(11)                            null,
    vehicle_requirements int                                    null,
    preferred_drivers    json                                   null,
    description          varchar(200)                           null,
    location_refrence    varchar(200)                           null,
    verified_by          varchar(33)                            null,
    notificationByEmail  tinyint      default 0                 not null,
    notificationBySms    tinyint      default 0                 not null
)
    charset = utf8mb3;

create fulltext index Index_Text
    on customers (businessName);

create fulltext index TEXT_postcode
    on customers (postcode);

create index cust_userid
    on customers (userID);

create index index_businessName
    on customers (businessName);

create index index_dBusinessName
    on customers (dBusinessName);

create index index_dPostcode
    on customers (dPostcode);

create index index_email1
    on customers (email1);

create index index_email2
    on customers (email2);

create index index_email3
    on customers (email3);

create index index_postcode
    on customers (postcode);

create index index_seller
    on customers (id_seller);

create index num_pc
    on customers (numerical_pc);

create table customers_b
(
    id               int                                    not null
        primary key,
    accountNo        varchar(100)                           null,
    agent            int          default 0                 null,
    businessName     varchar(255)                           null,
    email1           varchar(255)                           null,
    email2           varchar(255)                           null,
    email3           varchar(255)                           null,
    fax              varchar(100)                           null,
    mob              varchar(100)                           null,
    number           varchar(255)                           null,
    web              varchar(200)                           null,
    street           varchar(255)                           null,
    tel              varchar(100)                           null,
    town             varchar(255)                           null,
    postcode         varchar(20)                            null,
    dBusinessName    varchar(255)                           null,
    dNumber          varchar(255)                           null,
    dStreet          varchar(255)                           null,
    dTown            varchar(255)                           null,
    dPostcode        varchar(20)                            null,
    discount         double       default 0                 null,
    id_customer_type int                                    null,
    id_seller        int                                    null,
    isAccount        int          default 0                 null,
    paymentType      varchar(200)                           null,
    type             varchar(20)                            null,
    owes             double       default 0                 null,
    userID           varchar(200)                           null,
    company          varchar(255) default '0'               null,
    mail             int          default 1                 null,
    timestamp        timestamp    default CURRENT_TIMESTAMP null
)
    charset = utf8mb3;

create table delCustomers
(
    id int not null
        primary key
)
    charset = latin1;

create table deleted_orders
(
    id                         int                                     not null
        primary key,
    id_company                 int           default 0                 null,
    id_status                  int           default 1                 null comment 'This should be ‘id_order_status’; the values are form the ‘order_status’ table',
    cid                        int                                     null,
    delRoute                   varchar(32)                             null,
    endDate                    varchar(32)                             null,
    endTime                    varchar(32)                             null,
    lastMaintainer             varchar(48)   default ''                null,
    lastMaintainID             int                                     null,
    orderStatus                varchar(24)                             null,
    paid                       double        default 0                 null,
    startDate                  varchar(24)                             null,
    startTime                  varchar(24)                             null,
    staffName                  varchar(48)                             null,
    staffid                    int                                     null,
    total                      double        default 0                 null,
    dispatchType               varchar(32)   default 'Delivery'        null,
    collector                  varchar(32)                             null,
    colDelDate                 varchar(24)                             null,
    carrier                    varchar(32)                             null,
    orderType                  varchar(32)                             null,
    direct                     int                                     null,
    paymentType                varchar(32)                             null,
    delOrder                   int                                     null,
    authNo                     varchar(32)                             null,
    cardNo                     varchar(4)                              null,
    exp                        varchar(20)                             null,
    companyName                varchar(200)                            null,
    ouref                      varchar(400)                            null,
    time_stamp                 timestamp     default CURRENT_TIMESTAMP not null,
    proforma                   int           default 0                 not null,
    showVat                    int           default 0                 null,
    showNotes                  int           default 1                 null,
    serType                    varchar(100)  default 'sale'            null,
    icType                     varchar(100)  default 'invoice'         null,
    iNo                        varchar(100)                            null,
    ebayID                     varchar(64)                             null,
    vatAmount                  double                                  null,
    zeroVat                    int           default 0                 null,
    ebayStatus                 varchar(100)                            null,
    deliverBy                  varchar(100)  default '0'               not null,
    tx                         int           default 0                 not null,
    delTime                    varchar(20)                             null,
    delStat                    varchar(20)                             null,
    agent                      text                                    null,
    agentPaid                  int           default 0                 null,
    agentOwed                  double        default 0                 null,
    rate                       double        default 0                 null,
    txt                        int           default 0                 null,
    code                       varchar(64)                             null,
    parcelid                   varchar(20)                             null,
    neighbour                  varchar(400)  default '0'               null,
    aftersales                 int           default 0                 null,
    safePlace                  varchar(200)  default '0'               null,
    done                       int           default 0                 null,
    ebay                       varchar(100)  default '0'               null,
    notLoaded                  int           default 0                 null,
    p                          int           default 0                 null,
    wes                        int           default 0                 null,
    cStatus                    varchar(100)  default 'none'            null,
    colOrder                   int           default 0                 null,
    colTime                    varchar(10)                             null,
    colRoute                   varchar(100)                            null,
    extra                      varchar(10)                             null,
    beds                       varchar(255)  default '0'               null,
    delDate                    varchar(20)                             null,
    colID                      int           default 0                 null,
    delID                      int           default 0                 null,
    lat                        varchar(100)                            null,
    lng                        varchar(100)                            null,
    amazonID                   varchar(100)                            null,
    amzShipped                 int           default 0                 null,
    refund                     int           default 0                 null,
    postLoc                    varchar(50)   default '0'               null,
    post                       int           default 0                 null,
    sender                     varchar(200)  default '0'               null,
    stamp                      varchar(100)                            null,
    startStamp                 varchar(100)  default '0'               null,
    wholeError                 int           default 0                 null,
    mfError                    int           default 0                 null,
    feedbackScore              int           default 0                 null,
    delissue                   int           default 0                 null,
    defects                    int           default 0                 null,
    g_id                       varchar(100)  default '0'               null,
    g_num                      varchar(100)  default '0'               null,
    g_desc                     varchar(128)  default '0'               null,
    g_ship                     varchar(24)   default '0'               null,
    oos                        int           default 0                 null,
    mgmt                       int           default 0                 null,
    processed                  int           default 0                 null,
    paidBeds                   int           default 0                 null,
    pTime                      varchar(16)   default '0'               null,
    refundBox                  int           default 0                 null,
    id_delivery_route          int           default 0                 null,
    id_order_type              int           default 0                 null,
    id_payment_type            int           default 0                 null,
    hold                       int           default 0                 null,
    missed                     int           default 0                 null,
    urgent                     int           default 0                 null,
    retention                  int           default 0                 null,
    paypal                     int           default 0                 null,
    reopen                     int           default 0                 null,
    id_supplier                int           default 0                 null,
    paid_supplier              int           default 0                 null,
    invoice_made               int           default 0                 null,
    id_seller                  int           default 0                 null,
    seller_paid                int           default 0                 null,
    discount_amount            decimal(9, 2) default 0.00              null,
    discount_rate              decimal(6, 2) default 0.00              null comment 'expressed as a percentage',
    vat_paid                   decimal(6, 2) default 0.00              null comment 'expressed as a percentage',
    paypal_payment             text                                    null,
    last_trans_id              varchar(50)   default '0'               null,
    to_collect                 int           default 0                 null,
    is_split                   int           default 0                 null,
    voucher_code               varchar(64)                             null,
    is_shipped                 int           default 0                 null,
    paid_courier               int           default 0                 null,
    tnt_consignment            varchar(48)                             null,
    ajfoams_tracking_number    varchar(48)                             null,
    bedtatstic_tracking_number varchar(48)                             null,
    is_collected               int           default 0                 null,
    escalate                   int           default 0                 null,
    ordered                    int           default 0                 null,
    merch_ref                  varchar(50)                             null,
    trust_pilot                int           default 0                 null,
    stolen                     int           default 0                 null,
    tuffnells                  tinyint(1)    default 0                 null,
    order_number_part_0        char                                    null comment 'R',
    order_number_part_1        char(4)                                 null comment 'REQUEST_SOURCE_BEDS = ''B'';
REQUEST_SOURCE_EBAY = ''E'';
REQUEST_SOURCE_OTHER = ''T'';
REQUEST_SOURCE_STAFF = ''S'';',
    order_number_part_2        char(13)                                null comment 'uniqid()',
    order_number_part_3        char(3)                                 null comment 'mt_rand(1, 999)',
    order_number_part_4        char(4)                                 null comment 'EXTENSION_BASE = ''P'';
EXTENSION_EXCHANGE = ''E'';
EXTENSION_SPLIT_ORDER = ''P'';',
    sales_record               varchar(10)   default '0'               null,
    priority                   tinyint(1)    default 0                 null,
    manual_order               tinyint(1)    default 0                 null,
    delivery_issue             int           default 0                 null
)
    charset = utf8mb3;

create table deliveries
(
    oid int null
)
    charset = latin1;

create table delivery
(
    id int         not null
        primary key,
    I  varchar(45) null
)
    charset = latin1;

create table delivery_status
(
    id              int         not null
        primary key,
    delivery_status varchar(32) null
)
    charset = latin1;

create table devices
(
    id         int          not null
        primary key,
    imei       varchar(400) null,
    tel        varchar(20)  null,
    driver     int          null,
    token      text         null,
    created_at datetime     null,
    updated_at datetime     null
)
    charset = latin1;

create table dictionary
(
    name  varchar(64) default '' not null
        primary key,
    value varchar(255)           null,
    constraint name_UNIQUE
        unique (name)
)
    charset = latin1;

create table direct
(
    id       int          not null
        primary key,
    oid      int          null,
    name     varchar(255) null,
    number   varchar(100) null,
    street   varchar(100) null,
    town     varchar(100) null,
    postcode varchar(20)  null,
    tel      varchar(20)  null,
    mob      varchar(20)  null,
    email    varchar(200) null
)
    charset = utf8mb3;

create index `index-order_id`
    on direct (oid);

create table disp
(
    oid        int           null,
    date       varchar(20)   null,
    route      varchar(48)   null,
    num        int default 0 null,
    van        int default 1 null,
    id_route   int           null,
    created_at timestamp     null,
    updated_at timestamp     null,
    id_disp    int auto_increment
        primary key,
    height     int           null
)
    charset = latin1;

create index disp_oid_index
    on disp (oid);

create index disp_van_index
    on disp (van);

create index index_date
    on disp (date);

create index index_id_route
    on disp (id_route);

create index index_oid
    on disp (oid);

create table dispRoute
(
    id                int auto_increment
        primary key,
    date              varchar(20)                  null,
    driver            varchar(48)                  null,
    driverPaid        int         default 0        null,
    mate              varchar(48)                  null,
    matePaid          int         default 0        null,
    picker            varchar(100)                 null,
    payments          double      default 0        null,
    route             varchar(24)                  null,
    staff             varchar(24)                  null,
    startTime         varchar(20)                  null,
    status            varchar(24) default 'Routed' null,
    toCollect         double      default 0        null,
    vehicle           varchar(24)                  null,
    id_route          int                          null,
    created_at        timestamp                    null,
    updated_at        timestamp                    null,
    loader            varchar(45)                  null,
    driver_id         int                          null,
    endTime           time                         null,
    exp_delivery_time time                         null
)
    charset = latin1;

create index index_id_route
    on dispRoute (id_route);

create table disp_type_mappings
(
    id          bigint unsigned not null
        primary key,
    disptype_id int             not null,
    name        varchar(255)    not null,
    created_at  timestamp       null,
    updated_at  timestamp       null
)
    collate = utf8mb4_unicode_ci;

create table dispatch_type
(
    id            int         not null
        primary key,
    dispatch_type varchar(32) null
)
    charset = latin1;

create table dispatch_types
(
    id         bigint unsigned not null
        primary key,
    type       varchar(255)    not null,
    company_id int             null,
    created_at timestamp       null,
    updated_at timestamp       null
)
    collate = utf8mb4_unicode_ci;

create table divan_product_calculations
(
    id                      bigint unsigned auto_increment
        primary key,
    sku                     varchar(255) null,
    is_parent               bigint       null comment 'used for product type. 0 for parent',
    configurable_variations json         null,
    set_code                varchar(255) null,
    price                   double(8, 2) null,
    special_price           double(8, 2) null,
    percentage              double(8, 2) null,
    storage                 varchar(255) null,
    created_at              timestamp    null,
    updated_at              timestamp    null
)
    collate = utf8mb4_unicode_ci;

create table driverDays
(
    name                                       varchar(200)      null,
    day                                        varchar(100)      null,
    driver_id                                  bigint unsigned   null,
    start_time                                 varchar(255)      null,
    end_time                                   varchar(255)      null,
    start_driving_exactly_from_the_shift_start tinyint default 0 not null,
    id                                         bigint unsigned auto_increment
        primary key,
    checked                                    tinyint default 0 not null,
    start_day                                  varchar(200)      null,
    end_day                                    varchar(200)      null,
    date                                       date              null
)
    charset = latin1;

create table driverFines
(
    driver varchar(100)  null,
    amount double        null,
    paid   int default 0 null,
    date   varchar(20)   null,
    staff  varchar(100)  null,
    notes  varchar(1000) null,
    id     int auto_increment
        primary key
)
    charset = latin1;

create table driverLocs
(
    id         int auto_increment
        primary key,
    driver_id  int              null,
    lat        double default 0 null,
    lng        double default 0 null,
    created_at datetime         null,
    updated_at datetime         null
)
    charset = latin1;

create table driverPayments
(
    dates  varchar(600) null,
    amount double       null,
    date   varchar(20)  null,
    driver varchar(200) null,
    staff  varchar(200) null
)
    charset = latin1;

create table driver_fcm_tokens
(
    id         bigint unsigned auto_increment
        primary key,
    driver_id  bigint unsigned not null,
    device_id  varchar(255)    not null,
    fcm_token  varchar(255)    not null,
    created_at timestamp       null,
    updated_at timestamp       null
)
    collate = utf8mb4_unicode_ci;

create table driver_manifest_configurations
(
    id                      bigint unsigned auto_increment
        primary key,
    task                    int       null,
    order_reference         int       null,
    location_name           int       null,
    location_address        int       null,
    contact_number          int       null,
    second_contact          int       null,
    additional_instructions int       null,
    weight                  int       null,
    stop_time               int       null,
    origin_window           int       null,
    client_name             int       null,
    service_level           int       null,
    customer_signature      int       null,
    location_postcode       int       null,
    contact_person          int       null,
    priority                int       null,
    order_item              int       null,
    volume                  int       null,
    web_ref                 int       null,
    vehicle_requirements    int       null,
    location_instructions   int       null,
    area_of_control         int       null,
    distance                int       null,
    territory               int       null,
    created_at              timestamp null,
    updated_at              timestamp null
)
    collate = utf8mb4_unicode_ci;

create table drivers
(
    id                    int auto_increment
        primary key,
    name                  varchar(255)                    null,
    score                 int                             null,
    uName                 varchar(64)                     null,
    pwd                   varchar(255)                    null,
    num                   varchar(24)                     null,
    rate                  decimal(6, 2) default 65.00     null,
    user_id               int                             null,
    active                char          default '1'       null,
    created_at            timestamp                       null,
    updated_at            timestamp                       null,
    comment               varchar(255)                    null,
    external_id           varchar(255)                    null,
    vehicle               int                             null,
    cost_per_hour         varchar(255)                    null,
    alerts_for_performers varchar(255)                    null,
    distribution_centre   int                             null,
    territories           json                            null,
    start_of_day_location text collate utf8mb4_unicode_ci null,
    end_of_day_location   text collate utf8mb4_unicode_ci null,
    driving_limit         varchar(255)                    null,
    duty_time_limit       varchar(255)                    null,
    run_duration_limit    varchar(255)                    null,
    start_of_day_address  text collate utf8mb4_unicode_ci null,
    end_of_day_address    text collate utf8mb4_unicode_ci null
)
    charset = latin1;

create index index_name
    on drivers (name);

create table dummy_order_net_suites
(
    id               bigint unsigned auto_increment
        primary key,
    oid              int       not null,
    company_order_id int       not null,
    created_at       timestamp null,
    updated_at       timestamp null
)
    collate = utf8mb4_unicode_ci;

create table eInvoice
(
    date        varchar(100)             null,
    paid        int          default 0   null,
    id          int auto_increment
        primary key,
    time        varchar(100) default '0' null,
    company     varchar(50)              null,
    id_supplier int                      null
)
    charset = latin1;

create table eInvoiced
(
    date    varchar(100)             null,
    paid    int          default 0   null,
    id      int auto_increment
        primary key,
    time    varchar(100) default '0' null,
    company varchar(50)              null
)
    charset = latin1;

create table ebayAccounts
(
    name         varchar(400)   null,
    appID        varchar(400)   null,
    certID       varchar(400)   null,
    devID        varchar(400)   null,
    token        varchar(10000) null,
    folder       varchar(100)   null,
    id           int auto_increment
        primary key,
    compat_level int            null,
    last_edit    datetime       null
)
    charset = latin1;

create table ebayStock
(
    itemCode    varchar(200)             null,
    itemQty     int                      null,
    itemID      varchar(200)             null,
    itemSKU     varchar(200)             null,
    updateStock int          default 0   null,
    ebay        varchar(100) default '0' null,
    id          int auto_increment
        primary key
)
    charset = latin1;

create table emailSettings
(
    email      varchar(500)  null,
    pwd        varchar(500)  null,
    smtp       varchar(400)  null,
    name       varchar(100)  null,
    id         int auto_increment
        primary key,
    id_company int           null,
    tls        int default 0 null,
    outName    varchar(255)  null,
    ssl_one    varchar(255)  null,
    port       varchar(255)  null
)
    charset = latin1;

create table epdq_orders
(
    id     int auto_increment
        primary key,
    oid    int  null,
    result text null
)
    charset = latin1;

create table execution_notifies
(
    id                                                   bigint unsigned auto_increment
        primary key,
    en_dispat_noti_before_the_driver_completes_the_run   varchar(255) null,
    send_e_mail_notification_before_x_not_started_orders varchar(255) null,
    en_driver_noti_when_order_det_are_sent_to_mobile_dev varchar(255) null,
    created_at                                           timestamp    null,
    updated_at                                           timestamp    null
)
    collate = utf8mb4_unicode_ci;

create table execution_preferences
(
    id                                                     bigint unsigned auto_increment
        primary key,
    use_driver_manifest_template                           varchar(255) null,
    prevent_order_completion_before_collecting_a_signature varchar(255) null,
    prevent_order_completion_before_attaching_photo        varchar(255) null,
    attachments_upload_mode                                varchar(255) null,
    display_order_priorities_on_the_mobile_app             varchar(255) null,
    enable_order_item_checkbox                             varchar(255) null,
    enable_check_all_for_order_items                       varchar(255) null,
    items_default_state_checked                            varchar(255) null,
    display_price_info_on_the_mobile_app                   varchar(255) null,
    area_radius_for_track_trace_control_meters             varchar(255) null,
    send_order_details_by_timer                            varchar(255) null,
    send_order_details_at                                  varchar(255) null,
    created_at                                             timestamp    null,
    updated_at                                             timestamp    null
)
    collate = utf8mb4_unicode_ci;

create table feedback
(
    id      int auto_increment
        primary key,
    comment varchar(800)  null,
    oid     int           null,
    type    int default 0 null,
    used    int default 0 null
)
    charset = latin1;

create table flagged
(
    id     int auto_increment
        primary key,
    oid    int         null,
    reason varchar(80) null
)
    charset = latin1;

create table freeSat
(
    id         int auto_increment
        primary key,
    date       varchar(100) null,
    saturday   date         null,
    user_id    int          null,
    created_at timestamp    null,
    updated_at timestamp    null
)
    charset = latin1;

create table groupon_sku
(
    id    int auto_increment
        primary key,
    g_sku varchar(50) null,
    sku   varchar(50) null
)
    charset = latin1;

create table histories
(
    id                  bigint unsigned auto_increment
        primary key,
    oid                 int          not null,
    vehicle_id_previous int          null,
    vehicle_id_current  int          null,
    id_disp             int          null,
    id_disp_route       int          null,
    action              int          not null,
    `to`                varchar(255) null,
    `from`              varchar(255) null,
    user_id             int          null,
    created_at          timestamp    null,
    updated_at          timestamp    null
)
    collate = utf8mb4_unicode_ci;

create table history
(
    id        int auto_increment
        primary key,
    status    varchar(64) null,
    oid       int         null,
    timestamp timestamp   null
)
    charset = latin1;

create table import_export_settings
(
    id          bigint unsigned auto_increment
        primary key,
    file_format varchar(255) null,
    `separator` varchar(255) null,
    created_at  timestamp    null,
    updated_at  timestamp    null
)
    collate = utf8mb4_unicode_ci;

create table importers
(
    id         int unsigned auto_increment
        primary key,
    name       text      not null,
    class_name text      not null,
    created_at timestamp null,
    updated_at timestamp null
)
    collate = utf8mb3_unicode_ci;

create table info
(
    id          int auto_increment
        primary key,
    description text         null,
    datum       varchar(255) null,
    `group`     varchar(48)  null,
    type        int          null,
    created_at  timestamp    null,
    updated_at  timestamp    null
)
    charset = latin1;

create table invoiced
(
    oid        int                     null,
    date       varchar(20)             null,
    paid       int         default 0   null,
    id         int                     not null
        primary key,
    tot        double      default 0   null,
    paid_stamp varchar(50) default '0' null
)
    charset = latin1;

create table job_add_numirical_post_codes
(
    id            bigint unsigned auto_increment
        primary key,
    job_id        bigint unsigned null,
    customer_id   bigint unsigned null,
    error_message varchar(255)    null,
    created_at    timestamp       null,
    updated_at    timestamp       null
)
    collate = utf8mb4_unicode_ci;

create table job_companies
(
    id               bigint unsigned  not null
        primary key,
    job_id           bigint unsigned  null,
    company_id       bigint unsigned  null,
    total_orders     bigint default 0 not null,
    success_orders   bigint default 0 not null,
    duplicate_orders bigint default 0 not null,
    failed_orders    bigint default 0 not null,
    error_message    varchar(255)     null,
    created_at       timestamp        null,
    updated_at       timestamp        null
)
    collate = utf8mb4_unicode_ci;

create table job_message_generators
(
    id              bigint unsigned auto_increment
        primary key,
    job_id          bigint unsigned null,
    notification_id bigint unsigned null,
    error_message   varchar(255)    null,
    created_at      timestamp       null,
    updated_at      timestamp       null
)
    collate = utf8mb4_unicode_ci;

create table job_notifications
(
    id              bigint unsigned auto_increment
        primary key,
    job_id          bigint unsigned null,
    notification_id bigint unsigned null,
    disp_id         bigint unsigned null,
    order_id        bigint unsigned null,
    error_message   varchar(255)    null,
    created_at      timestamp       null,
    updated_at      timestamp       null
)
    collate = utf8mb4_unicode_ci;

create table job_orders
(
    id            bigint unsigned auto_increment
        primary key,
    company_id    bigint unsigned null,
    order_id      bigint unsigned null,
    duplicate     varchar(255)    null,
    error_message varchar(255)    null,
    created_at    timestamp       null,
    updated_at    timestamp       null
)
    collate = utf8mb4_unicode_ci;

create table job_sended_messages
(
    id                bigint unsigned auto_increment
        primary key,
    job_id            bigint unsigned null,
    sended_message_id bigint unsigned null,
    error_message     varchar(255)    null,
    created_at        timestamp       null,
    updated_at        timestamp       null
)
    collate = utf8mb4_unicode_ci;

create table job_statistics
(
    id                bigint unsigned auto_increment
        primary key,
    job_name          varchar(255)     null,
    error_message     varchar(255)     null,
    total_entries     bigint default 0 not null,
    failed_entries    bigint default 0 not null,
    success_entries   bigint default 0 not null,
    duplicate_intries bigint default 0 not null,
    created_at        timestamp        null,
    updated_at        timestamp        null
)
    collate = utf8mb4_unicode_ci;

create table jobs
(
    id           bigint unsigned auto_increment
        primary key,
    queue        varchar(255)     not null,
    payload      longtext         not null,
    attempts     tinyint unsigned not null,
    reserved_at  int unsigned     null,
    available_at int unsigned     not null,
    created_at   int unsigned     not null
)
    collate = utf8mb4_unicode_ci;

create table links
(
    oid       int                                          null,
    code      varchar(400)                                 null,
    sent      int          default 0                       null,
    done      int          default 0                       null,
    status    varchar(100) default 'Pending delivery date' null,
    time      varchar(40)                                  null,
    timestamp timestamp    default CURRENT_TIMESTAMP       null,
    id_col    int                                          not null
        primary key
)
    charset = latin1;

create index links_code
    on links (code);

create index oid
    on links (oid);

create index oid_2
    on links (oid);

create table localization
(
    id             int default 1 not null,
    currency       varchar(200)  not null,
    distance_units varchar(200)  not null
);

create table log
(
    ip      varchar(100) null,
    time    varchar(20)  null,
    forward varchar(200) null,
    id      int          not null
        primary key
)
    charset = latin1;

create table logins
(
    id    int                                not null
        primary key,
    ip    varchar(45)                        null,
    proxy varchar(45)                        null,
    user  int                                null,
    local varchar(45)                        null,
    time  datetime default CURRENT_TIMESTAMP null
)
    charset = latin1;

create table logins_w
(
    id    int                                not null
        primary key,
    ip    varchar(45)                        null,
    proxy varchar(45)                        null,
    user  int                                null,
    local varchar(45)                        null,
    time  datetime default CURRENT_TIMESTAMP null
)
    charset = latin1;

create table m_error_notes
(
    id         int                      not null
        primary key,
    note       varchar(255)             null,
    cost       float(8, 2) default 0.00 null,
    staff_id   int         default 0    null,
    created_at datetime                 null,
    updated_at datetime                 null,
    oid        int                      null
)
    charset = latin1;

create index ORDER_INDEX
    on m_error_notes (oid);

create table magento
(
    id   int         not null
        primary key,
    time varchar(45) null
)
    charset = latin1;

create table magento_accounts
(
    id          int                     not null
        primary key,
    user        varchar(150)            null,
    password    varchar(200)            null,
    active      varchar(45) default '0' null,
    url         varchar(255)            null,
    api_version double                  null
)
    charset = latin1;

create table magento_links
(
    id   int         not null
        primary key,
    code varchar(20) null
)
    charset = latin1;

create index code
    on magento_links (code);

create table mail_lists
(
    id             int         not null
        primary key,
    description    text        null,
    emails         text        null,
    sent           timestamp   null,
    title          varchar(32) null,
    user_id        int         null,
    email_temaplte varchar(24) null
)
    charset = latin1;

create table mailer
(
    id   int auto_increment
        primary key,
    time varchar(100) null,
    camp int          null
)
    charset = latin1;

create table manufacturing_error
(
    id         int                      not null
        primary key,
    order_id   int                      null,
    problem    varchar(45)              null,
    created_at datetime                 null,
    updated_at datetime                 null,
    item_id    int                      null,
    cost       float(8, 2) default 0.00 null
)
    charset = latin1;

create index Item_Index
    on manufacturing_error (item_id);

create index Order_Index
    on manufacturing_error (order_id);

create table mateDays
(
    name varchar(200) null,
    day  varchar(100) null
)
    charset = latin1;

create table matePayments
(
    dates  varchar(600) null,
    amount double       null,
    mate   varchar(200) null,
    date   varchar(200) null,
    staff  varchar(200) null
)
    charset = latin1;

create table mates
(
    id         int                        not null
        primary key,
    name       varchar(100)               null,
    num        varchar(24)                null,
    rate       decimal(8, 2) default 0.00 null,
    matePaid   int           default 0    null,
    created_at timestamp                  null,
    updated_at timestamp                  null,
    active     int           default 0    null
)
    charset = latin1;

create table maxDays
(
    date   varchar(100)   null,
    route  varchar(200)   null,
    num    int            null,
    max    int default 30 null,
    weight double         null,
    cbm    double         null,
    van    int default 1  null
)
    charset = latin1;

create table max_optra_orders
(
    id         bigint unsigned not null
        primary key,
    ref_no     varchar(255)    not null,
    status     varchar(255)    not null,
    created_at timestamp       null,
    updated_at timestamp       null
)
    collate = utf8mb4_unicode_ci;

create table messages
(
    id              int unsigned                 not null
        primary key,
    conversation_id int                          null,
    folder          varchar(48) charset utf8mb3  null,
    from_id         int                          null,
    is_read         tinyint(1) default 0         null,
    message         text charset utf8mb3         null,
    subject         varchar(128) charset utf8mb3 null,
    to_id           int                          null,
    created_at      timestamp                    null,
    deleted_at      timestamp                    null,
    updated_at      timestamp                    null,
    new             tinyint(1) default 1         null
)
    collate = utf8mb3_unicode_ci;

create index convo_index
    on messages (conversation_id);

create index new_index
    on messages (new);

create index sender_index
    on messages (from_id);

create index to_index
    on messages (to_id);

create table migrations
(
    id        int unsigned auto_increment
        primary key,
    migration varchar(255) not null,
    batch     int          not null
)
    collate = utf8mb3_unicode_ci;

create table model_has_permissions
(
    permission_id bigint unsigned not null,
    model_type    varchar(255)    not null,
    model_id      bigint unsigned not null,
    primary key (permission_id, model_id, model_type)
)
    collate = utf8mb4_unicode_ci;

create index model_has_permissions_model_id_model_type_index
    on model_has_permissions (model_id, model_type);

create table net_suite_orders
(
    id         bigint unsigned not null
        primary key,
    oid        int             not null,
    created_at timestamp       null,
    updated_at timestamp       null
)
    collate = utf8mb4_unicode_ci;

create table notification_message_controles
(
    id         bigint unsigned      not null
        primary key,
    approved   tinyint(1) default 0 not null,
    created_at timestamp            null,
    updated_at timestamp            null
)
    collate = utf8mb4_unicode_ci;

create table notification_messages
(
    id             int unsigned         not null
        primary key,
    notificationID int unsigned         not null,
    recipient      varchar(255)         not null,
    recipient_name varchar(255)         not null,
    body           mediumtext           not null,
    subject        varchar(255)         null,
    msgMethod      varchar(255)         not null,
    created_at     timestamp            null,
    updated_at     timestamp            null,
    is_sent        tinyint(1) default 0 not null,
    approved       tinyint(1) default 0 not null,
    err_message    varchar(255)         null,
    date_sent      timestamp            null
)
    collate = utf8mb3_unicode_ci;

create index nm_nid
    on notification_messages (notificationID);

create table notifications
(
    id         int unsigned         not null
        primary key,
    customerID int unsigned         not null,
    orderID    int unsigned         not null,
    dispID     int unsigned         null,
    type       varchar(255)         not null,
    hasSent    tinyint(1)           not null,
    created_at timestamp            null,
    updated_at timestamp            null,
    cantsend   tinyint(1) default 0 not null
)
    collate = utf8mb3_unicode_ci;

create index not_date
    on notifications (created_at);

create index not_dispid
    on notifications (dispID);

create index not_orderid
    on notifications (orderID);

create index not_orderid_type
    on notifications (orderID, type);

create table oauth_access_tokens
(
    id         varchar(100) not null
        primary key,
    user_id    int          null,
    client_id  int          not null,
    name       varchar(255) null,
    scopes     text         null,
    revoked    tinyint(1)   not null,
    created_at timestamp    null,
    updated_at timestamp    null,
    expires_at datetime     null
)
    collate = utf8mb3_unicode_ci;

create index oauth_access_tokens_user_id_index
    on oauth_access_tokens (user_id);

create table oauth_auth_codes
(
    id         varchar(100) not null
        primary key,
    user_id    int          not null,
    client_id  int          not null,
    scopes     text         null,
    revoked    tinyint(1)   not null,
    expires_at datetime     null
)
    collate = utf8mb3_unicode_ci;

create table oauth_clients
(
    id                     int unsigned not null
        primary key,
    user_id                int          null,
    name                   varchar(255) not null,
    secret                 varchar(100) not null,
    redirect               text         not null,
    personal_access_client tinyint(1)   not null,
    password_client        tinyint(1)   not null,
    revoked                tinyint(1)   not null,
    created_at             timestamp    null,
    updated_at             timestamp    null
)
    collate = utf8mb3_unicode_ci;

create index oauth_clients_user_id_index
    on oauth_clients (user_id);

create table oauth_personal_access_clients
(
    id         int unsigned not null
        primary key,
    client_id  int          not null,
    created_at timestamp    null,
    updated_at timestamp    null
)
    collate = utf8mb3_unicode_ci;

create index oauth_personal_access_clients_client_id_index
    on oauth_personal_access_clients (client_id);

create table oauth_refresh_tokens
(
    id              varchar(100) not null
        primary key,
    access_token_id varchar(100) not null,
    revoked         tinyint(1)   not null,
    expires_at      datetime     null
)
    collate = utf8mb3_unicode_ci;

create index oauth_refresh_tokens_access_token_id_index
    on oauth_refresh_tokens (access_token_id);

create table oos_search
(
    id         int                                not null
        primary key,
    items      text                               null,
    created_at datetime default CURRENT_TIMESTAMP null,
    updated_at datetime                           null
)
    charset = latin1;

create table operatio_durations
(
    id                                    bigint unsigned auto_increment
        primary key,
    fixed_loading_duration                varchar(255) null,
    variable_loading_duration_per_unit    varchar(255) null,
    fixed_time_per_address                varchar(255) null,
    fixed_time_per_order                  varchar(255) null,
    variable_time_per_capacity_delivery   varchar(255) null,
    variable_time_per_capacity_collection varchar(255) null,
    fixed_un_loading_duration             varchar(255) null,
    variable_un_loading_duration_per_unit varchar(255) null,
    created_at                            timestamp    null,
    updated_at                            timestamp    null,
    customer_id                           int          null
)
    collate = utf8mb4_unicode_ci;

create table orderItems
(
    id                   int auto_increment comment '20.01 changed to int(11)'
        primary key,
    id_staff             int                null,
    id_item_status       int                null,
    itemid               int                null,
    oid                  int                null,
    bedsName             text               null,
    blocked              int      default 0 null,
    currStock            int                null comment 'changed to int(6) ',
    discount             double   default 0 null,
    itemStatus           varchar(200)       null,
    lineTotal            double             null,
    notes                text               null,
    price                decimal(8, 2)      null,
    Qty                  int                null,
    staffName            varchar(100)       null,
    costs                double   default 0 null,
    barcode              varchar(200)       null,
    item_ex_id           varchar(200)       null,
    item_actual_quantity int                null,
    status               varchar(200)       null,
    is_rejected          tinyint  default 0 null,
    rejected_reason      varchar(255)       null,
    comments             varchar(255)       null,
    qty_rejected         smallint default 0 null,
    qty_scanned          smallint default 0 null
)
    charset = utf8mb3;

create index index_order_id
    on orderItems (oid);

create index itemid
    on orderItems (itemid);

create table orderItems_b
(
    id                   int                not null comment '20.01 changed to int(11)'
        primary key,
    id_staff             int                null,
    id_item_status       int                null,
    itemid               int                null,
    oid                  int                null,
    bedsName             text               null,
    blocked              int      default 0 null,
    currStock            int                null comment 'changed to int(6) ',
    discount             double   default 0 null,
    itemStatus           varchar(100)       null,
    lineTotal            double             null,
    notes                text               null,
    price                decimal(8, 2)      null,
    Qty                  int                null,
    staffName            varchar(100)       null,
    costs                double   default 0 null,
    reason_id            bigint unsigned    null,
    comment              varchar(255)       null,
    image_path           varchar(255)       null,
    barcode              varchar(255)       null,
    item_ex_id           varchar(255)       null,
    item_actual_quantity int                null,
    is_rejected          tinyint  default 0 null,
    rejected_reason      varchar(255)       null,
    comments             varchar(255)       null,
    qty_rejected         smallint default 0 null,
    qty_scanned          smallint default 0 null
)
    charset = utf8mb3;

create table orderNotes
(
    id         int                  not null
        primary key,
    oid        int                  null,
    date       varchar(40)          null,
    time       varchar(20)          null,
    notes      text                 null,
    staff      varchar(100)         null,
    driver     int        default 0 null,
    pic        tinyint(1) default 0 null,
    id_staff   int                  null,
    id_driver  int                  null,
    created_at datetime             null,
    updated_at datetime             null
)
    charset = utf8mb3;

create index oid
    on orderNotes (oid);

create table orderPayments
(
    id              int          not null
        primary key,
    stamp           varchar(200) null,
    paid            double       null,
    cardNo          varchar(50)  null,
    exp             varchar(20)  null,
    auth            varchar(100) null,
    oid             int          null,
    type            varchar(100) null,
    staff           varchar(200) null,
    id_staff        int          null,
    id_payment_type int          null
)
    charset = latin1;

create index op_oid
    on orderPayments (oid);

create table order_authorisations
(
    id            int unsigned auto_increment
        primary key,
    staff_id      int unsigned not null,
    order_id      int unsigned not null,
    requested_by  int unsigned not null,
    authorised_by int unsigned not null,
    action_type   varchar(50)  not null,
    created_at    timestamp    null,
    updated_at    timestamp    null,
    reason        varchar(255) null
)
    collate = utf8mb3_unicode_ci;

create table order_cancellation_reasons
(
    id         bigint unsigned auto_increment
        primary key,
    name       text      null,
    created_at timestamp null,
    updated_at timestamp null
)
    collate = utf8mb4_unicode_ci;

create table order_cancellation_reasons_for_ksys
(
    id     bigint unsigned auto_increment
        primary key,
    slug   varchar(50)  not null,
    reason varchar(100) not null
)
    collate = utf8mb4_unicode_ci;

create table order_events
(
    order_id   int                  not null,
    event_type varchar(255)         not null,
    done       tinyint(1) default 0 not null,
    created_at timestamp            null,
    updated_at timestamp            null,
    primary key (order_id, event_type)
)
    collate = utf8mb3_unicode_ci;

create table order_images
(
    id         bigint unsigned auto_increment
        primary key,
    order_id   int          null,
    item_id    int          null,
    type       varchar(100) null,
    path       varchar(100) null,
    created_at timestamp    null,
    updated_at timestamp    null
)
    collate = utf8mb4_unicode_ci;

create table order_item_cancellation_reasons
(
    id         bigint unsigned auto_increment
        primary key,
    name       text      null,
    created_at timestamp null,
    updated_at timestamp null
)
    collate = utf8mb4_unicode_ci;

create table order_item_rejected_images
(
    id            bigint unsigned auto_increment
        primary key,
    order_item_id int          not null,
    image         varchar(255) null,
    created_at    timestamp    null,
    updated_at    timestamp    null,
    type          varchar(50)  null
)
    collate = utf8mb4_unicode_ci;

create table order_opertion_time_windows
(
    id         bigint unsigned auto_increment
        primary key,
    order_id   int          null,
    date       varchar(255) null,
    start_time varchar(255) null,
    end_time   varchar(255) null,
    created_at timestamp    null,
    updated_at timestamp    null
)
    collate = utf8mb4_unicode_ci;

create table order_rejection_details
(
    id         bigint unsigned auto_increment
        primary key,
    order_id   int          not null,
    reason_id  smallint     null,
    comments   varchar(100) null,
    created_at timestamp    null,
    updated_at timestamp    null
)
    collate = utf8mb4_unicode_ci;

create table order_report
(
    id           int auto_increment
        primary key,
    order_id     varchar(20)                          not null,
    date_created datetime                             not null,
    channel      smallint                             not null comment '1-beds',
    status       tinyint(1) default 0                 not null,
    created      timestamp  default CURRENT_TIMESTAMP not null
)
    engine = MyISAM
    charset = latin1;

create table order_sources
(
    id         bigint unsigned auto_increment
        primary key,
    name       varchar(255) not null,
    created_at timestamp    null,
    updated_at timestamp    null
)
    collate = utf8mb4_unicode_ci;

create table order_status
(
    id           int auto_increment
        primary key,
    order_status varchar(32)   null,
    link_status  varchar(128)  null,
    show_status  int default 1 null
)
    charset = latin1;

create index idx_order_status_link_status
    on order_status (link_status);

create index idx_order_status_order_status
    on order_status (order_status);

create table order_status_history
(
    id               int auto_increment
        primary key,
    changed_datetime datetime default CURRENT_TIMESTAMP null,
    from_status      varchar(24)                        null,
    order_id         int                                null,
    text             text                               null,
    to_status        varchar(24)                        null
)
    charset = latin1;

create table order_tracking_widgets
(
    id             int auto_increment
        primary key,
    widgetDomain   varchar(22)  not null,
    widgetLocation varchar(800) not null,
    rateDelivery   int          not null
);

create table order_type
(
    id         int auto_increment
        primary key,
    order_type varchar(32) null
)
    charset = latin1;

create table orders
(
    id                         int auto_increment
        primary key,
    id_company                 int           default 0                 null,
    company_order_id           int                                     null,
    id_status                  int           default 1                 null comment 'This should be ‘id_order_status’; the values are form the ‘order_status’ table',
    cid                        int                                     null,
    ref_no                     varchar(255)                            null,
    delRoute                   varchar(32)                             null,
    endDate                    varchar(32)                             null,
    endTime                    varchar(32)                             null,
    lastMaintainer             varchar(48)   default ''                null,
    lastMaintainID             int                                     null,
    orderStatus                varchar(24)                             null,
    paid                       double        default 0                 null,
    startDate                  varchar(24)                             null,
    startTime                  varchar(24)                             null,
    staffName                  varchar(48)                             null,
    staffid                    int                                     null,
    total                      double        default 0                 null,
    dispatchType               varchar(32)   default 'Delivery'        null,
    collector                  varchar(32)                             null,
    colDelDate                 varchar(24)                             null,
    carrier                    varchar(32)                             null,
    orderType                  varchar(32)                             null,
    direct                     int                                     null,
    paymentType                varchar(32)                             null,
    delOrder                   int                                     null,
    authNo                     varchar(32)                             null,
    cardNo                     varchar(4)                              null,
    exp                        varchar(20)                             null,
    companyName                varchar(200)                            null,
    ouref                      varchar(400)                            null,
    time_stamp                 timestamp     default CURRENT_TIMESTAMP not null,
    proforma                   int           default 0                 not null,
    showVat                    int           default 0                 null,
    showNotes                  int           default 1                 null,
    serType                    varchar(100)  default 'sale'            null,
    icType                     varchar(100)  default 'invoice'         null,
    iNo                        varchar(100)                            null,
    ebayID                     varchar(64)                             null,
    vatAmount                  double                                  null,
    zeroVat                    int           default 0                 null,
    ebayStatus                 varchar(100)                            null,
    deliverBy                  varchar(100)  default '0'               not null,
    tx                         int           default 0                 not null,
    delTime                    varchar(20)                             null,
    delStat                    varchar(20)                             null,
    agent                      text                                    null,
    agentPaid                  int           default 0                 null,
    agentOwed                  double        default 0                 null,
    rate                       double        default 0                 null,
    txt                        int           default 0                 null,
    code                       varchar(64)                             null,
    parcelid                   varchar(20)                             null,
    neighbour                  varchar(400)  default '0'               null,
    aftersales                 int           default 0                 null,
    safePlace                  varchar(200)  default '0'               null,
    done                       int           default 0                 null,
    ebay                       varchar(100)  default '0'               null,
    notLoaded                  int           default 0                 null,
    p                          int           default 0                 null,
    wes                        int           default 0                 null,
    cStatus                    varchar(100)  default 'none'            null,
    colOrder                   int           default 0                 null,
    colTime                    varchar(10)                             null,
    colRoute                   varchar(100)                            null,
    extra                      varchar(10)                             null,
    beds                       varchar(255)  default '0'               null,
    delDate                    varchar(20)                             null,
    colID                      int           default 0                 null,
    delID                      int           default 0                 null,
    lat                        varchar(100)                            null,
    lng                        varchar(100)                            null,
    amazonID                   varchar(100)                            null,
    amzShipped                 int           default 0                 null,
    refund                     int           default 0                 null,
    postLoc                    varchar(50)   default '0'               null,
    post                       int           default 0                 null,
    sender                     varchar(200)  default '0'               null,
    stamp                      varchar(100)                            null,
    startStamp                 varchar(100)  default '0'               null,
    wholeError                 int           default 0                 null,
    mfError                    int           default 0                 null,
    feedbackScore              int           default 0                 null,
    delissue                   int           default 0                 null,
    defects                    int           default 0                 null,
    g_id                       varchar(100)  default '0'               null,
    g_num                      varchar(100)  default '0'               null,
    g_desc                     varchar(128)  default '0'               null,
    g_ship                     varchar(24)   default '0'               null,
    oos                        int           default 0                 null,
    mgmt                       int           default 0                 null,
    processed                  int           default 0                 null,
    paidBeds                   int           default 0                 null,
    pTime                      varchar(16)   default '0'               null,
    refundBox                  int           default 0                 null,
    id_delivery_route          int           default 0                 null,
    id_order_type              int           default 0                 null,
    id_payment_type            int           default 0                 null,
    hold                       int           default 0                 null,
    missed                     int           default 0                 null,
    urgent                     int           default 0                 null,
    retention                  int           default 0                 null,
    paypal                     int           default 0                 null,
    reopen                     int           default 0                 null,
    id_supplier                int           default 0                 null,
    paid_supplier              int           default 0                 null,
    invoice_made               int           default 0                 null,
    id_seller                  int           default 0                 null,
    seller_paid                int           default 0                 null,
    discount_amount            decimal(9, 2) default 0.00              null,
    discount_rate              decimal(6, 2) default 0.00              null comment 'expressed as a percentage',
    vat_paid                   decimal(6, 2) default 0.00              null comment 'expressed as a percentage',
    paypal_payment             text                                    null,
    last_trans_id              varchar(50)   default '0'               null,
    to_collect                 int           default 0                 null,
    is_split                   int           default 0                 null,
    voucher_code               varchar(64)                             null,
    is_shipped                 int           default 0                 null,
    paid_courier               int           default 0                 null,
    tnt_consignment            varchar(48)                             null,
    ajfoams_tracking_number    varchar(48)                             null,
    bedtatstic_tracking_number varchar(48)                             null,
    is_collected               int           default 0                 null,
    escalate                   int           default 0                 null,
    ordered                    int           default 0                 null,
    merch_ref                  varchar(50)                             null,
    trust_pilot                int           default 0                 null,
    stolen                     int           default 0                 null,
    tuffnells                  tinyint(1)    default 0                 null,
    order_number_part_0        char                                    null comment 'R',
    order_number_part_1        char(4)                                 null comment 'REQUEST_SOURCE_BEDS = ''B'';
REQUEST_SOURCE_EBAY = ''E'';
REQUEST_SOURCE_OTHER = ''T'';
REQUEST_SOURCE_STAFF = ''S'';',
    order_number_part_2        char(13)                                null comment 'uniqid()',
    order_number_part_3        char(3)                                 null comment 'mt_rand(1, 999)',
    order_number_part_4        char(4)                                 null comment 'EXTENSION_BASE = ''P'';
EXTENSION_EXCHANGE = ''E'';
EXTENSION_SPLIT_ORDER = ''P'';',
    sales_record               varchar(10)   default '0'               null,
    priority                   varchar(255)  default '0'               null,
    manual_order               tinyint(1)    default 0                 null,
    delivery_issue             int           default 0                 null,
    parent_order_id            int unsigned                            null,
    invoiceStatus              tinyint(1)    default 0                 null,
    invoiced_time_stamp        varchar(100)                            null,
    invoicePaidStatus          tinyint(1)    default 0                 null,
    invoicePaid_time_stamp     varchar(100)                            null,
    warehouse_id               int                                     null,
    vehicle_requirement_id     varchar(255)                            null,
    territory_id               varchar(255)                            null,
    speed_zone_id              varchar(255)                            null,
    description                text                                    null,
    collection                 text                                    not null,
    weight                     text                                    null,
    volume                     float                                   null,
    deliverBy_datetime         datetime                                null,
    error                      int                                     null,
    max_parent_order_id        int                                     null,
    operation_duration         varchar(11)                             null,
    total_distance             float                                   null,
    total_duration             float                                   null,
    locked                     int           default 1                 not null,
    stop_sequence              varchar(255)                            null,
    allowNotifications         json                                    null,
    return_distance            text                                    null,
    return_duration            text                                    null,
    loadingTime                varchar(255)                            null,
    waitingTime                varchar(255)                            null,
    workStartTime              varchar(255)                            null,
    returnStartTime            varchar(255)                            null,
    returnWareHouseTime        varchar(255)                            null,
    arriveTime                 varchar(255)                            null,
    operationEndTime           varchar(255)                            null,
    runDuration                varchar(255)                            null,
    runTotalDrivingTime        varchar(255)                            null,
    timeViolation              varchar(255)                            null,
    weightExceeded             varchar(255)                            null,
    howMuchWeightOver          varchar(255)                            null,
    volumeExceeded             varchar(255)                            null,
    howMuchVolumeOver          varchar(255)                            null,
    takenloadingTime           varchar(255)                            null,
    takenstartTime             varchar(255)                            null,
    takenwaitingTime           varchar(255)                            null,
    takenworkStartTime         varchar(255)                            null,
    takenreturnStartTime       varchar(255)                            null,
    takenreturnWareHouseTime   varchar(255)                            null,
    takenarriveTime            varchar(255)                            null,
    takenoperationEndTime      varchar(255)                            null,
    takenrunDuration           varchar(255)                            null,
    takenrunTotalDrivingTime   varchar(255)                            null,
    takentimeViolation         varchar(255)                            null,
    takenweightExceeded        varchar(255)                            null,
    takenhowMuchWeightOver     varchar(255)                            null,
    takenvolumeExceeded        varchar(255)                            null,
    takenhowMuchVolumeOver     varchar(255)                            null
)
    charset = utf8mb3;

create index deliverBy_datetime
    on orders (deliverBy_datetime);

create index id_company
    on orders (id_company);

create index id_company_2
    on orders (id_company);

create index id_company_3
    on orders (id_company);

create index max_parent_order_id
    on orders (max_parent_order_id);

create index orders_deliverby_datetime_index
    on orders (deliverBy_datetime);

create index orders_max_parent_order_id_index
    on orders (max_parent_order_id);

create index orders_warehouse_id_index
    on orders (warehouse_id);

create table orders_b
(
    id                         int auto_increment
        primary key,
    id_company                 int           default 0                 null,
    id_status                  int           default 1                 null comment 'This should be ‘id_order_status’; the values are form the ‘order_status’ table',
    cid                        int                                     null,
    delRoute                   varchar(32)                             null,
    endDate                    varchar(32)                             null,
    endTime                    varchar(32)                             null,
    lastMaintainer             varchar(48)   default ''                null,
    lastMaintainID             int                                     null,
    orderStatus                varchar(24)                             null,
    paid                       double        default 0                 null,
    startDate                  varchar(24)                             null,
    startTime                  varchar(24)                             null,
    staffName                  varchar(48)                             null,
    staffid                    int                                     null,
    total                      double        default 0                 null,
    dispatchType               varchar(32)   default 'Delivery'        null,
    collector                  varchar(32)                             null,
    colDelDate                 varchar(24)                             null,
    carrier                    varchar(32)                             null,
    orderType                  varchar(32)                             null,
    direct                     int                                     null,
    paymentType                varchar(32)                             null,
    delOrder                   int                                     null,
    authNo                     varchar(32)                             null,
    cardNo                     varchar(4)                              null,
    exp                        varchar(20)                             null,
    companyName                varchar(200)                            null,
    ouref                      varchar(400)                            null,
    time_stamp                 timestamp     default CURRENT_TIMESTAMP not null,
    proforma                   int           default 0                 not null,
    showVat                    int           default 0                 null,
    showNotes                  int           default 1                 null,
    serType                    varchar(100)  default 'sale'            null,
    icType                     varchar(100)  default 'invoice'         null,
    iNo                        varchar(100)                            null,
    ebayID                     varchar(64)                             null,
    vatAmount                  double                                  null,
    zeroVat                    int           default 0                 null,
    ebayStatus                 varchar(100)                            null,
    deliverBy                  varchar(100)  default '0'               not null,
    tx                         int           default 0                 not null,
    delTime                    varchar(20)                             null,
    delStat                    varchar(20)                             null,
    agent                      text                                    null,
    agentPaid                  int           default 0                 null,
    agentOwed                  double        default 0                 null,
    rate                       double        default 0                 null,
    txt                        int           default 0                 null,
    code                       varchar(64)                             null,
    parcelid                   varchar(20)                             null,
    neighbour                  varchar(400)  default '0'               null,
    aftersales                 int           default 0                 null,
    safePlace                  varchar(200)  default '0'               null,
    done                       int           default 0                 null,
    ebay                       varchar(100)  default '0'               null,
    notLoaded                  int           default 0                 null,
    p                          int           default 0                 null,
    wes                        int           default 0                 null,
    cStatus                    varchar(100)  default 'none'            null,
    colOrder                   int           default 0                 null,
    colTime                    varchar(10)                             null,
    colRoute                   varchar(100)                            null,
    extra                      varchar(10)                             null,
    beds                       varchar(255)  default '0'               null,
    delDate                    varchar(20)                             null,
    colID                      int           default 0                 null,
    delID                      int           default 0                 null,
    lat                        varchar(100)                            null,
    lng                        varchar(100)                            null,
    amazonID                   varchar(100)                            null,
    amzShipped                 int           default 0                 null,
    refund                     int           default 0                 null,
    postLoc                    varchar(50)   default '0'               null,
    post                       int           default 0                 null,
    sender                     varchar(200)  default '0'               null,
    stamp                      varchar(100)                            null,
    startStamp                 varchar(100)  default '0'               null,
    wholeError                 int           default 0                 null,
    mfError                    int           default 0                 null,
    feedbackScore              int           default 0                 null,
    delissue                   int           default 0                 null,
    defects                    int           default 0                 null,
    g_id                       varchar(100)  default '0'               null,
    g_num                      varchar(100)  default '0'               null,
    g_desc                     varchar(128)  default '0'               null,
    g_ship                     varchar(24)   default '0'               null,
    oos                        int           default 0                 null,
    mgmt                       int           default 0                 null,
    processed                  int           default 0                 null,
    paidBeds                   int           default 0                 null,
    pTime                      varchar(16)   default '0'               null,
    refundBox                  int           default 0                 null,
    id_delivery_route          int           default 0                 null,
    id_order_type              int           default 0                 null,
    id_payment_type            int           default 0                 null,
    hold                       int           default 0                 null,
    missed                     int           default 0                 null,
    urgent                     int           default 0                 null,
    retention                  int           default 0                 null,
    paypal                     int           default 0                 null,
    reopen                     int           default 0                 null,
    id_supplier                int           default 0                 null,
    paid_supplier              int           default 0                 null,
    invoice_made               int           default 0                 null,
    id_seller                  int           default 0                 null,
    seller_paid                int           default 0                 null,
    discount_amount            decimal(9, 2) default 0.00              null,
    discount_rate              decimal(6, 2) default 0.00              null comment 'expressed as a percentage',
    vat_paid                   decimal(6, 2) default 0.00              null comment 'expressed as a percentage',
    paypal_payment             text                                    null,
    last_trans_id              varchar(50)   default '0'               null,
    to_collect                 int           default 0                 null,
    is_split                   int           default 0                 null,
    voucher_code               varchar(64)                             null,
    is_shipped                 int           default 0                 null,
    paid_courier               int           default 0                 null,
    tnt_consignment            varchar(48)                             null,
    ajfoams_tracking_number    varchar(48)                             null,
    bedtatstic_tracking_number varchar(48)                             null,
    is_collected               int           default 0                 null,
    escalate                   int           default 0                 null,
    ordered                    int           default 0                 null,
    merch_ref                  varchar(50)                             null,
    trust_pilot                int           default 0                 null,
    stolen                     int           default 0                 null,
    tuffnells                  tinyint(1)    default 0                 null,
    order_number_part_0        char                                    null comment 'R',
    order_number_part_1        char(4)                                 null comment 'REQUEST_SOURCE_BEDS = ''B'';
REQUEST_SOURCE_EBAY = ''E'';
REQUEST_SOURCE_OTHER = ''T'';
REQUEST_SOURCE_STAFF = ''S'';',
    order_number_part_2        char(13)                                null comment 'uniqid()',
    order_number_part_3        char(3)                                 null comment 'mt_rand(1, 999)',
    order_number_part_4        char(4)                                 null comment 'EXTENSION_BASE = ''P'';
EXTENSION_EXCHANGE = ''E'';
EXTENSION_SPLIT_ORDER = ''P'';',
    sales_record               varchar(10)   default '0'               null,
    priority                   tinyint(1)    default 0                 null
)
    charset = utf8mb3;

create table orders_log
(
    id          int auto_increment
        primary key,
    ip          varchar(45)  null,
    proxy       varchar(45)  null,
    oid         int          null,
    local       varchar(45)  null,
    user_id     int          null,
    actions     varchar(255) null,
    status      varchar(255) null,
    eta         varchar(255) null,
    timeAndDate datetime     not null
)
    charset = latin1;

create table out_of_stock
(
    id         int auto_increment
        primary key,
    item_id    int      null,
    created_at datetime null,
    updated_at datetime null
)
    charset = latin1;

create table packages
(
    id          bigint unsigned auto_increment
        primary key,
    name        varchar(255)   not null,
    description text           null,
    price       decimal(10, 2) not null,
    created_at  timestamp      null,
    updated_at  timestamp      null
)
    collate = utf8mb4_unicode_ci;

create table packs
(
    id  int auto_increment
        primary key,
    l   varchar(50) null,
    h   varchar(50) null,
    w   varchar(50) null,
    we  varchar(50) null,
    oid int         null
)
    charset = latin1;

create table page_visits
(
    id   int auto_increment
        primary key,
    oid  int                                null,
    ip   varchar(45)                        null,
    time datetime default CURRENT_TIMESTAMP null,
    user int                                null
)
    charset = latin1;

create table password_resets
(
    email      varchar(255)                        not null,
    token      varchar(255)                        not null,
    created_at timestamp default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP
)
    collate = utf8mb3_unicode_ci;

create table passwords
(
    id   int auto_increment
        primary key,
    type varchar(150) null,
    pwd  varchar(150) null
)
    charset = latin1;

create table payment_type
(
    id           int auto_increment
        primary key,
    admin        int default 0 null,
    description  text          null,
    payment_type varchar(32)   null,
    created_at   timestamp     null,
    updated_at   timestamp     null
)
    charset = latin1;

create table pda_data
(
    id                    int auto_increment
        primary key,
    driver_pda_version    float(8, 2) null,
    warehouse_pda_version float(8, 2) null,
    created_at            datetime    null,
    updated_at            datetime    null
)
    charset = latin1;

create table permissions
(
    id          bigint unsigned auto_increment
        primary key,
    name        varchar(255) not null,
    guard_name  varchar(255) not null,
    created_at  timestamp    null,
    updated_at  timestamp    null,
    description longtext     null,
    constraint permissions_name_guard_name_unique
        unique (name, guard_name)
)
    collate = utf8mb4_unicode_ci;

create table personal_access_tokens
(
    id             bigint unsigned auto_increment
        primary key,
    tokenable_type varchar(255)    not null,
    tokenable_id   bigint unsigned not null,
    name           varchar(255)    not null,
    token          varchar(64)     not null,
    abilities      text            null,
    last_used_at   timestamp       null,
    expires_at     timestamp       null,
    created_at     timestamp       null,
    updated_at     timestamp       null,
    constraint personal_access_tokens_token_unique
        unique (token)
)
    collate = utf8mb4_unicode_ci;

create index personal_access_tokens_tokenable_type_tokenable_id_index
    on personal_access_tokens (tokenable_type, tokenable_id);

create table pickers
(
    id         int auto_increment
        primary key,
    name       varchar(100)         null,
    pwd        varchar(200)         null,
    uName      varchar(200)         null,
    num        varchar(45)          null,
    created_at datetime             null,
    updated_at datetime             null,
    active     tinyint(1) default 1 null
)
    charset = latin1;

create table plannin_preferences
(
    id                                         bigint unsigned auto_increment
        primary key,
    enable_toll_roads                          varchar(255) default 'no' not null,
    allow_several_runs_for_a_vehicle_per_day   varchar(255) default 'no' not null,
    territories_planning_mode                  varchar(255)              null,
    working_time_before_break                  varchar(255)              null,
    enable_on_board_time_limit_for_deliveries  varchar(255) default 'no' not null,
    on_board_time_limit_for_deliveries         varchar(255)              null,
    enable_on_board_time_limit_for_collections varchar(255) default 'no' not null,
    on_board_time_limit_for_collections        varchar(255)              null,
    created_at                                 timestamp                 null,
    updated_at                                 timestamp                 null
)
    collate = utf8mb4_unicode_ci;

create table postcode_areas
(
    id   int auto_increment
        primary key,
    code varchar(12)  null,
    area varchar(200) null
)
    charset = latin1;

create table postcode_delivery_exceptions
(
    id         int auto_increment
        primary key,
    postcode   varchar(12) null,
    created_at timestamp   null,
    updated_at timestamp   null
)
    charset = latin1;

create table processes
(
    pid  int          null,
    name varchar(400) null,
    link varchar(400) null,
    id   int auto_increment
        primary key
)
    charset = latin1;

create table proof
(
    oid  int          null,
    date varchar(20)  null,
    time varchar(20)  null,
    link varchar(200) null
)
    charset = latin1;

create table queries
(
    id         int auto_increment
        primary key,
    query      text     null,
    created_at datetime null,
    updated_at datetime null
)
    charset = latin1;

create table refunds
(
    id          int auto_increment
        primary key,
    date        varchar(100)     null,
    total       double default 0 null,
    staff_id    varchar(45)      null,
    paymentType varchar(100)     null
)
    charset = latin1;

create table reminders
(
    id     int auto_increment
        primary key,
    note   varchar(1000) null,
    date   varchar(20)   null,
    time   varchar(20)   null,
    done   int default 0 null,
    setBy  varchar(100)  null,
    setFor varchar(100)  null
)
    charset = latin1;

create table report_data_items
(
    id        int auto_increment
        primary key,
    company   varchar(48)  null,
    count     int          null,
    date      date         null,
    item_code varchar(255) null
)
    charset = latin1;

create table report_data_orders
(
    id          int auto_increment
        primary key,
    company     varchar(48)    null,
    count       int            null,
    orders_date date           null,
    orders_type varchar(48)    null,
    paid        decimal(14, 2) null,
    vat         decimal(14, 2) null,
    total       decimal(14, 2) null
)
    charset = latin1;

create table role_has_permissions
(
    permission_id bigint unsigned not null,
    role_id       bigint unsigned not null,
    primary key (permission_id, role_id)
)
    collate = utf8mb4_unicode_ci;

create index role_has_permissions_role_id_foreign
    on role_has_permissions (role_id);

create table role_user
(
    user_id int unsigned not null,
    role_id int unsigned not null,
    primary key (user_id, role_id)
)
    collate = utf8mb3_unicode_ci;

create table roles
(
    id         bigint unsigned auto_increment
        primary key,
    name       varchar(255) not null,
    guard_name varchar(255) not null,
    created_at timestamp    null,
    updated_at timestamp    null,
    constraint roles_name_guard_name_unique
        unique (name, guard_name)
)
    collate = utf8mb4_unicode_ci;

create table model_has_roles
(
    role_id    bigint unsigned not null,
    model_type varchar(255)    not null,
    model_id   bigint unsigned not null,
    primary key (role_id, model_id, model_type),
    constraint model_has_roles_role_id_foreign
        foreign key (role_id) references roles (id)
            on delete cascade
)
    collate = utf8mb4_unicode_ci;

create index model_has_roles_model_id_model_type_index
    on model_has_roles (model_id, model_type);

create table routeDays
(
    route       varchar(48)    null,
    day         varchar(24)    null,
    max         int default 30 null,
    route_id    int            null,
    routeDaysID int            not null
)
    charset = latin1;

create table routeName
(
    id             int auto_increment
        primary key,
    name           varchar(48)           null,
    max            int        default 30 null,
    van            int        default 1  null,
    msg            int        default 0  null,
    is_wholesale   tinyint(1) default 0  null,
    in_load_count  tinyint(1) default 1  null,
    is_route_route tinyint(1) default 0  null,
    updated_at     timestamp             null,
    created_at     timestamp             null
)
    charset = utf8mb3;

create table routeStops
(
    id       int auto_increment
        primary key,
    routeID  int         null,
    postCode varchar(20) null
)
    engine = MyISAM
    charset = utf8mb3;

create table sellers
(
    id             int auto_increment
        primary key,
    account_limit  int        default 0 null,
    discount       double     default 0 null,
    email          varchar(255)         null,
    iName          varchar(64)          null,
    name           varchar(255)         null,
    num            varchar(100)         null,
    pc             varchar(32)          null,
    pwd            varchar(128)         null,
    password       varchar(255)         null,
    salt           varchar(128)         null,
    street         varchar(128)         null,
    tel            varchar(40)          null,
    town           varchar(64)          null,
    custom_price   int        default 0 null,
    force_pay      int        default 1 null,
    remember_token varchar(100)         null,
    created_at     timestamp            null,
    updated_at     timestamp            null,
    active_seller  tinyint(1) default 1 null
)
    charset = latin1;

create table sender
(
    id   int auto_increment
        primary key,
    name varchar(128) null
)
    charset = latin1;

create table sentmail
(
    id   int auto_increment
        primary key,
    camp int null
)
    charset = latin1;

create table settings
(
    id            int auto_increment
        primary key,
    vat           double         null,
    showNotes     int            null,
    cod           int default 1  null,
    wholesale     int default 1  null,
    companies     varchar(10000) null,
    payments      varchar(10000) null,
    bedsEmail     varchar(10000) null,
    bedsEmailSend int default 0  null,
    maps_key      varchar(300)   null
)
    charset = latin1;

create table skus
(
    sku      varchar(200) null,
    original varchar(200) null
)
    charset = latin1;

create table sms_email_formats
(
    id                                  bigint unsigned auto_increment
        primary key,
    actions                             varchar(255)            null,
    delay_sending_duration_silent_hours varchar(255)            null,
    send_at                             varchar(255)            null,
    sms_messages                        varchar(255)            null,
    email_subject                       varchar(255)            null,
    email_messages                      varchar(255)            null,
    sms                                 int                     null,
    email                               int                     null,
    start_time                          varchar(255)            null,
    end_time                            varchar(255)            null,
    created_at                          timestamp               null,
    updated_at                          timestamp               null,
    attachPod                           int  default 0          not null,
    fileName                            varchar(255)            null,
    NumberOfDaysBeforePlannedArrival    int  default 0          not null,
    sentAt                              time default '00:00:00' not null,
    NotificationBefore                  int  default 0          not null
)
    collate = utf8mb4_unicode_ci;

create table speed_zones
(
    id                     bigint unsigned auto_increment
        primary key,
    name                   varchar(255) not null,
    coordinates            text         not null,
    radius                 text         not null,
    speed_correction_error varchar(255) null,
    color                  varchar(255) null,
    created_at             timestamp    null,
    updated_at             timestamp    null
)
    collate = utf8mb4_unicode_ci;

create table split_table_categories
(
    id           int auto_increment
        primary key,
    categorty_id int         null,
    category     varchar(48) null,
    created_at   timestamp   null,
    updated_at   timestamp   null
)
    charset = latin1;

create table staff
(
    id              int auto_increment
        primary key,
    fName           varchar(100)         null,
    sName           varchar(100)         null,
    uName           varchar(255)         null,
    pwd             varchar(255)         null,
    level           tinyint(1) default 0 null,
    id_staff_status int                  null,
    id_staff_type   int                  null,
    avatar          varchar(48)          null,
    salt            varchar(128)         null,
    last_change     timestamp            null on update CURRENT_TIMESTAMP
)
    charset = utf8mb3;

create table staff_read_bulletin
(
    staff_id    int null,
    bulletin_id int null comment 'The bulletin records read by a member of staff.'
)
    charset = latin1;

create table staffpass
(
    id    int auto_increment
        primary key,
    uname varchar(100) null,
    pass  varchar(100) null
)
    engine = MyISAM
    charset = latin1;

create table stf_group
(
    id          int auto_increment
        primary key,
    group_name  varchar(32) null,
    description text        null
)
    charset = latin1;

create table stf_group_resource_perms
(
    id_group    int         null,
    permissions varchar(4)  null,
    resource    varchar(48) null,
    id_resource int         null
)
    charset = latin1;

create table stf_staff_group
(
    id_staff int null,
    id_group int not null
)
    charset = latin1;

create table stf_staff_status
(
    id           int auto_increment
        primary key,
    staff_status varchar(32) null
)
    charset = latin1;

create table stf_staff_type
(
    id         int auto_increment
        primary key,
    staff_type varchar(32) null
)
    charset = latin1;

create table stockDetail
(
    sid             int        null,
    weight          double     null,
    id              int auto_increment
        primary key,
    cbm             double     null,
    length          float      null,
    width           float      null,
    height          float      null,
    two_man_lift    tinyint(1) null,
    greater_than_1m tinyint(1) null,
    bulky           tinyint(1) null,
    updated_at      timestamp  not null,
    created_at      timestamp  not null
)
    charset = latin1;

create table stock_categories
(
    id          int auto_increment
        primary key,
    name        varchar(200) null,
    description text         null
)
    charset = latin1;

create table stock_items
(
    id                   int auto_increment comment '20.01 changed to int(11)'
        primary key,
    cost                 double         default 0     null,
    itemCode             varchar(255)                 null,
    itemName             varchar(100)                 null,
    itemDescription      text                         null comment '20.01 changed to text',
    id_company           tinyint                      null,
    sms                  tinyint        default 1     not null,
    itemQty              int                          null,
    itemAlloc            int                          null,
    itemOnOrder          int                          null,
    needs_attention      tinyint(1)     default 1     null,
    retail               double         default 0     null,
    wholesale            double         default 0     null,
    weight               decimal(11, 3) default 0.000 null,
    dimensions           varchar(100)   default '0'   null,
    bin                  varchar(100)                 null,
    actual               int            default 0     null comment '20.01 changed to int(11)',
    pieces               int            default 1     null,
    isMulti              varchar(24)    default '1'   null comment 'the number of boxes for the item',
    warehouse            int                          null,
    label                int            default 1     null,
    blocked              int            default 1     null,
    seller               int            default 0     null,
    cat                  varchar(100)   default ''    null,
    qty_in_stock         int            default 0     null,
    qty_on_so            int            default 0     null,
    qty_ordered          int            default 0     null,
    category_id          int            default 0     null,
    beds_price           double         default 0     null,
    ebay_price           double         default 0     null,
    route_id             int            default 0     null,
    colour               varchar(24)                  null,
    gross_weight         decimal(6, 2)                null,
    manifest_description text                         null,
    model_no             varchar(24)                  null,
    net_weight           decimal(6, 2)                null,
    cbm                  double                       null,
    two_man_lift         tinyint                      null,
    greater_than_1m      tinyint                      null,
    parts                double                       null,
    bulky                double                       null,
    thickness            double                       null,
    `cube`               double                       null,
    height               double                       null,
    length               double                       null,
    width                double                       null,
    product_size         varchar(196)                 null,
    stock_category_id    int                          null,
    withdrawn            tinyint(1)     default 0     null,
    always_in_stock      tinyint(1)     default 0     null,
    created_at           timestamp                    null,
    updated_at           timestamp                    null,
    isActive             tinyint(1)     default 1     not null
)
    charset = utf8mb3;

create index index_itemCode
    on stock_items (itemCode);

create index index_model_no
    on stock_items (model_no);

create index itemCode
    on stock_items (itemCode);

create table stock_items_backup
(
    id                   int auto_increment comment '20.01 changed to int(11)'
        primary key,
    cost                 double         default 0     null,
    itemCode             varchar(255)                 null,
    itemName             varchar(100)                 null,
    itemDescription      text                         null comment '20.01 changed to text',
    sms                  tinyint        default 1     not null,
    itemQty              int                          null,
    itemAlloc            int                          null,
    itemOnOrder          int                          null,
    needs_attention      tinyint(1)     default 1     null,
    retail               double         default 0     null,
    wholesale            double         default 0     null,
    weight               decimal(11, 3) default 0.000 null,
    dimensions           varchar(100)   default '0'   null,
    bin                  varchar(100)                 null,
    actual               int            default 0     null comment '20.01 changed to int(11)',
    pieces               int            default 1     null,
    isMulti              varchar(24)    default '1'   null comment 'the number of boxes for the item',
    warehouse            int                          null,
    label                int            default 1     null,
    blocked              int            default 1     null,
    seller               int            default 0     null,
    cat                  varchar(100)   default ''    null,
    qty_in_stock         int            default 0     null,
    qty_on_so            int            default 0     null,
    qty_ordered          int            default 0     null,
    category_id          int            default 0     null,
    beds_price           double         default 0     null,
    ebay_price           double         default 0     null,
    route_id             int            default 0     null,
    colour               varchar(24)                  null,
    gross_weight         decimal(6, 2)                null,
    manifest_description text                         null,
    model_no             varchar(24)                  null,
    net_weight           decimal(6, 2)                null,
    product_size         varchar(196)                 null,
    stock_category_id    int                          null,
    withdrawn            tinyint(1)     default 0     null,
    always_in_stock      tinyint(1)     default 0     null,
    created_at           timestamp                    null,
    updated_at           timestamp                    null,
    isActive             tinyint(1)     default 1     not null
)
    charset = utf8mb3;

create table stock_parts
(
    id        int auto_increment
        primary key,
    parent_id int          null,
    part      varchar(255) null,
    quantity  int          null
)
    charset = latin1;

create table stocktranslate
(
    fromid int not null,
    toid   int not null
)
    charset = latin1;

create table sub_packages
(
    id          bigint unsigned auto_increment
        primary key,
    name        varchar(255)   not null,
    description text           null,
    price       decimal(10, 2) not null,
    created_at  timestamp      null,
    updated_at  timestamp      null
)
    collate = utf8mb4_unicode_ci;

create table supplier
(
    id                       int auto_increment
        primary key,
    supplier_name            varchar(255)         null,
    days                     int        default 0 null,
    print_orders_on_dispatch tinyint(1) default 0 null,
    updated_at               timestamp            null,
    created_at               timestamp            null,
    del_route_id             int        default 0 null,
    route_max                int        default 0 null,
    name                     varchar(100)         null,
    email                    varchar(200)         null,
    password                 varchar(255)         null,
    remember_token           varchar(100)         null
)
    charset = latin1;

create table supplier_days
(
    route       varchar(200)   null,
    day         varchar(100)   null,
    max         int default 30 null,
    supplier_id int            null,
    route_id    int            null
)
    charset = latin1;

create table territorie_driver
(
    id            bigint unsigned auto_increment
        primary key,
    territorie_id int       null,
    driver_id     int       null,
    created_at    timestamp null,
    updated_at    timestamp null
)
    collate = utf8mb4_unicode_ci;

create table territorie_vehicle
(
    id            bigint unsigned auto_increment
        primary key,
    territorie_id int       null,
    vehicle_id    int       null,
    created_at    timestamp null,
    updated_at    timestamp null
)
    collate = utf8mb4_unicode_ci;

create table territories
(
    id           bigint unsigned auto_increment
        primary key,
    name         varchar(255) not null,
    ref_no       varchar(255) not null,
    coordinates  text         not null,
    radius       text         not null,
    warehouse_id varchar(255) null,
    color        varchar(255) null,
    group_id     varchar(255) null,
    created_at   timestamp    null,
    updated_at   timestamp    null
)
    collate = utf8mb4_unicode_ci;

create table territories_groups
(
    id           bigint unsigned auto_increment
        primary key,
    name         varchar(255) null,
    created_at   timestamp    null,
    updated_at   timestamp    null,
    warehouse_id int          null
)
    collate = utf8mb4_unicode_ci;

create table time_windows
(
    id         bigint unsigned auto_increment
        primary key,
    data       varchar(255) null,
    `from`     varchar(255) not null,
    `to`       varchar(255) not null,
    created_at timestamp    null,
    updated_at timestamp    null
)
    collate = utf8mb4_unicode_ci;

create table toCol
(
    oid  int           null,
    tel  int default 1 null,
    mail int default 1 null,
    date varchar(20)   null
)
    charset = latin1;

create table toShip
(
    oid  int           null,
    tel  int default 1 null,
    mail int default 1 null,
    date varchar(20)   null
)
    charset = latin1;

create table tolls
(
    date   varchar(20)   null,
    time   varchar(20)   null,
    driver varchar(100)  null,
    reg    varchar(30)   null,
    type   varchar(100)  null,
    paid   int default 0 null
)
    charset = latin1;

create table tried
(
    id  int auto_increment
        primary key,
    oid int null
)
    charset = latin1;

create table txt_inbox
(
    num    varchar(100) null,
    date   varchar(20)  null,
    msg    varchar(400) null,
    oid    int          null,
    action varchar(200) null
)
    charset = latin1;

create table txts
(
    num  varchar(50) null,
    oid  int         null,
    date varchar(20) null
)
    charset = latin1;

create table updates
(
    itemCode varchar(200) null
)
    charset = latin1;

create table user_bookmarks
(
    id          int auto_increment
        primary key,
    user_id     int          null,
    url         varchar(255) null,
    description varchar(128) null,
    created_at  timestamp    null,
    updated_at  timestamp    null
)
    charset = latin1;

create table user_fcm_tokens
(
    id         bigint unsigned auto_increment
        primary key,
    user_id    bigint unsigned not null,
    device_id  varchar(255)    not null,
    fcm_token  varchar(255)    not null,
    created_at timestamp       null,
    updated_at timestamp       null
)
    collate = utf8mb4_unicode_ci;

create table user_read_bulletins
(
    user_id     int       not null,
    bulletin_id text      not null,
    created_at  timestamp null,
    updated_at  timestamp null
)
    collate = utf8mb3_unicode_ci;

create table users
(
    id                                 int unsigned auto_increment
        primary key,
    name                               varchar(255)      not null,
    email                              varchar(255)      not null,
    password                           varchar(255)      not null,
    company_id                         bigint unsigned   null,
    remember_token                     varchar(100)      null,
    created_at                         timestamp         null,
    updated_at                         timestamp         null,
    status                             tinyint default 1 null,
    second_name                        varchar(255)      null,
    active_token                       varchar(255)      null,
    api_access                         varchar(255)      null,
    allocated_warehouses_as_dispatcher text              null,
    allocated_warehouses_as_customer   text              null,
    language                           varchar(200)      null,
    admin_access                       int     default 0 null
)
    collate = utf8mb3_unicode_ci;

create table vehicleDays
(
    reg varchar(200) null,
    day varchar(100) null
)
    charset = latin1;

create table vehicleNotes
(
    id    int auto_increment
        primary key,
    note  varchar(1000) null,
    date  varchar(20)   null,
    time  varchar(20)   null,
    vid   int           null,
    miles double        null
)
    charset = latin1;

create table vehicle_cost_types
(
    id          int         not null,
    description text        null,
    type        varchar(48) null,
    created_at  timestamp   null,
    updated_at  timestamp   null
)
    charset = latin1;

create table vehicle_costs
(
    id                   int auto_increment
        primary key,
    cost                 decimal(11, 2) null,
    date_incurred        date           null,
    mileage              int            null,
    notes                text           null,
    user_id              int            null,
    vehicle_cost_type_id int            null,
    vehicle_id           int            null,
    created_at           timestamp      null,
    updated_at           timestamp      null
)
    charset = latin1;

create table vehicle_requirments
(
    id                                bigint unsigned auto_increment
        primary key,
    name                              varchar(255) null,
    ref_no                            varchar(255) null,
    working_time_before_break         varchar(255) null,
    incompatible_vehicle_requirements varchar(255) null,
    created_at                        timestamp    null,
    updated_at                        timestamp    null
)
    collate = utf8mb4_unicode_ci;

create table vehicle_settings
(
    id                             bigint unsigned auto_increment
        primary key,
    driver_id                      int          null,
    vehicle_id                     int          null,
    supported_vehicle_requirements json         null,
    vehicle_type                   varchar(200) null,
    max_speed                      int          null,
    driving_time_correction_factor int          null,
    cost_per_mile                  int          null,
    vehicle_activation_cost        int          null,
    cost_per_order                 int          null,
    capacityWeight                 int          null,
    driving_time                   time         null,
    Run_duration_limit             time         null,
    duty_time_limit                time         null,
    automatic_break_shift          tinyint      null,
    cost_per_hour                  int          null,
    run_distance_limit             int          null,
    forDate                        date         null,
    time                           time         null,
    created_at                     timestamp    null,
    updated_at                     timestamp    null
)
    collate = utf8mb4_unicode_ci;

create table vehicle_types
(
    id                                   bigint unsigned auto_increment
        primary key,
    name                                 varchar(255) null,
    ref_no                               varchar(255) null,
    working_time_before_break            varchar(255) null,
    routing_mode                         varchar(255) null,
    avoid_urban_areas                    varchar(255) null,
    avoid_london_ultra_low_emission_zone varchar(255) null,
    weight                               varchar(222) null,
    height                               varchar(222) null,
    width                                varchar(222) null,
    length                               varchar(222) null,
    axle_load                            varchar(222) null,
    created_at                           timestamp    null,
    updated_at                           timestamp    null
)
    collate = utf8mb4_unicode_ci;

create table vehicles
(
    id                             int auto_increment
        primary key,
    reg                            varchar(30)                 null,
    height                         decimal(10, 2) default 0.00 null,
    width                          decimal(10, 2) default 0.00 null,
    depth                          decimal(10, 2) default 0.00 null,
    weight                         decimal(10, 2) default 0.00 null,
    description                    text                        null,
    user_id                        int                         null,
    created_at                     timestamp                   null,
    updated_at                     timestamp                   null,
    name                           varchar(255)                null,
    vehicle_type                   varchar(255)                null,
    assigned_device                varchar(255)                null,
    tcp_source                     varchar(255)                null,
    supported_vehicle              varchar(255)                null,
    max_speed                      varchar(255)                null,
    driving_time_correction_factor varchar(255)                null,
    cost_per_mile                  varchar(255)                null,
    vehicle_activation_cost        varchar(255)                null,
    cost_per_order                 varchar(255)                null,
    capacity_weight                varchar(255)                null,
    run_distance_limit             varchar(255)                null,
    distribution_centre_id         int                         null,
    driver_id                      int                         null,
    external_id                    varchar(255)                null,
    territories                    varchar(255)                null,
    comment                        varchar(255)                null,
    manufacturer_info              varchar(255)                null,
    vin                            varchar(255)                null,
    stand_down                     varchar(255)                null,
    archived                       varchar(255)                null,
    color                          varchar(255)                null,
    id_route                       varchar(20)                 null,
    volume                         float                       null
)
    charset = latin1;

create index vehicles_distribution_centre_id_index
    on vehicles (distribution_centre_id);

create table warehouse_dispitchers
(
    id            bigint unsigned auto_increment
        primary key,
    dispitcher_id int       null,
    warehouse_id  int       null,
    created_at    timestamp null,
    updated_at    timestamp null
)
    collate = utf8mb4_unicode_ci;

create table warehouse_errors
(
    id         int auto_increment
        primary key,
    note       varchar(200) null,
    item       varchar(45)  null,
    oid        int          null,
    created_at datetime     null,
    updated_at datetime     null
)
    charset = latin1;

create table warehouse_log
(
    id                 int auto_increment
        primary key,
    created_at         datetime     null,
    updated_at         datetime     null,
    message            varchar(255) null,
    staff_id           int          null,
    warehouse_id       int          null,
    warehouse_stock_id int          null
)
    charset = latin1;

create table warehouse_stock
(
    id             int auto_increment
        primary key,
    warehouse_id   int null,
    stock_parts_id int null,
    quantity       int null
)
    charset = latin1;

create table warehouses
(
    id                             int auto_increment
        primary key,
    name                           int           null,
    location                       varchar(48)   null,
    user_id                        varchar(255)  null,
    ref_number                     varchar(255)  null,
    address_prefix                 varchar(255)  null,
    phone                          varchar(255)  null,
    dispatcher                     json          null,
    driving_time_correction_factor varchar(255)  null,
    daily_driving_limt             varchar(255)  null,
    duty_time_limt                 varchar(255)  null,
    run_duration_limt              varchar(255)  null,
    collection_after_deliveries    varchar(255)  null,
    start_fo_day_location          varchar(255)  null,
    end_of_day_location            varchar(255)  null,
    sunday                         json          null,
    monday                         json          null,
    tuesday                        json          null,
    wednesday                      json          null,
    thursday                       json          null,
    friday                         json          null,
    saturday                       json          null,
    holidays                       json          null,
    latitude                       varchar(200)  null,
    longitude                      varchar(200)  null,
    postalCode                     varchar(200)  null,
    name_of_warehouse              text          null,
    location_of_warehouse          text          null,
    checked                        int default 0 not null,
    visitDistributionCenter        text          null,
    vistDistributionLocation       text          null
)
    charset = latin1;

create table wholesale
(
    id         int auto_increment
        primary key,
    id_seller  int       null,
    id_stock   int       null,
    cost       double    null,
    created_at timestamp null,
    updated_at timestamp null
)
    charset = latin1;

create table whstaff
(
    id    int auto_increment
        primary key,
    name  varchar(400) null,
    uName varchar(100) null,
    pwd   varchar(200) null
)
    charset = latin1;

create table wowcherinvoice
(
    wid                int                  not null,
    winvoiceId         int                  not null,
    worderId           int                  not null,
    winvoicePaidStatus tinyint(1) default 0 not null,
    winvoicedDate      varchar(100)         not null,
    wpaidDate          varchar(100)         not null,
    wtotal             float                not null
)
    charset = latin1;


