Доступные методы
================

Последовательность работы:

1. Нужно аутентифицироваться и авторизоваться в API с помощью auth.sid и ключа интегратора.

2. Затем получить список доступных организаций и их идентификаторы с помощью метода organizations.

3. По идентификатору организации нужно получить список касс с помощью метода cashboxes.

4. По РН ККТ кассы, полученному в п.3, и идентификатору организации, полученному в п.2, нужно получить документы или статистику.


.. toctree::
    :name: Auth
    :maxdepth: 1
    :caption: Авторизация и аутентификация

    Auth/authenticate-by-pass
    Auth/authenticate-by-cert
    Auth/approve-cert
	
.. toctree::
    :name: Orgs
    :maxdepth: 1
    :caption: Работа с организациями и кассами

    http/organizations
    http/organization
    http/cashboxes
    http/cashbox	

.. toctree::
    :name: Docs
    :maxdepth: 1
    :caption: Работа с документами

    http/documents-by-period
    http/documents
    http/documentId
    http/document
    http/tickets

.. toctree::
    :name: Stat
    :maxdepth: 1
    :caption: Статистика

    http/cashboxes-statistics-by-days
    http/cashboxes-statistics-by-shifts
    http/organizations-statistics-by-days
	