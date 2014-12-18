USE [Zak_Magento]
GO
/****** Object:  User [ZP\PSIAdmin2]    Script Date: 12/16/2014 16:41:36 ******/
CREATE USER [ZP\PSIAdmin2] FOR LOGIN [ZP\PSIAdmin2] WITH DEFAULT_SCHEMA=[dbo]
GO
/****** Object:  Table [dbo].[tblSOAdjustment]    Script Date: 12/16/2014 16:41:36 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[tblSOAdjustment](
	[MagSalesOrderNo] [nvarchar](13) NOT NULL,
	[MagLineNo] [nvarchar](7) NOT NULL,
	[LineKey] [nvarchar](6) NULL,
	[MagChange] [nvarchar](250) NULL,
	[SageStatus] [nvarchar](250) NULL,
	[ReceivedDate] [datetime] NULL,
	[SentToSage] [datetime] NULL,
	[AutoNbr] [int] IDENTITY(1,1) NOT NULL,
	[SalesOrderNo] [nvarchar](7) NULL,
	[SageAdjustedDate] [datetime] NULL,
	[SageAdjusted] [bit] NULL,
 CONSTRAINT [PK_tblSOAdjustment] PRIMARY KEY CLUSTERED 
(
	[AutoNbr] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[tblShippedTracking]    Script Date: 12/16/2014 16:41:36 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[tblShippedTracking](
	[MagSalesOrderNo] [nvarchar](13) NOT NULL,
	[SageInvoiceNo] [nvarchar](7) NOT NULL,
	[PackageNo] [nvarchar](4) NOT NULL,
	[TrackingID] [nvarchar](30) NULL,
	[ShipDate] [datetime] NULL,
	[ShipMethod] [nvarchar](50) NULL,
	[ShipCarrier] [nvarchar](50) NULL,
	[ShipVia] [nvarchar](15) NULL,
	[ShippedTrackingAutoNumber] [int] IDENTITY(1,1) NOT NULL,
	[SentToMagento] [bit] NOT NULL,
 CONSTRAINT [PK_tblShippedTracking] PRIMARY KEY CLUSTERED 
(
	[MagSalesOrderNo] ASC,
	[SageInvoiceNo] ASC,
	[PackageNo] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[tblShippedByItem]    Script Date: 12/16/2014 16:41:36 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[tblShippedByItem](
	[MagSalesOrderNo] [nvarchar](13) NOT NULL,
	[SageInvoiceNo] [nvarchar](7) NOT NULL,
	[MagLineNo] [nvarchar](7) NOT NULL,
	[SageInvoiceDate] [datetime] NULL,
	[ShipDate] [datetime] NULL,
	[ShipMethod] [nvarchar](50) NULL,
	[ShipCarrier] [nvarchar](50) NULL,
	[ItemCode] [nvarchar](30) NULL,
	[ShipQuantity] [decimal](18, 0) NULL,
	[ShipVia] [nvarchar](15) NULL,
	[ShippedByItemAutoNumber] [int] IDENTITY(1,1) NOT NULL,
 CONSTRAINT [PK_tblShippedByItem] PRIMARY KEY CLUSTERED 
(
	[MagSalesOrderNo] ASC,
	[SageInvoiceNo] ASC,
	[MagLineNo] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[tblSalesOrderHeader]    Script Date: 12/16/2014 16:41:36 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[tblSalesOrderHeader](
	[MagSalesOrderNo] [nvarchar](13) NOT NULL,
	[SalesOrderNo] [nvarchar](7) NULL,
	[OrderDate] [date] NULL,
	[OrderType] [nvarchar](1) NULL,
	[OrderStatus] [nvarchar](1) NULL,
	[ShipExpireDate] [date] NULL,
	[ARDivisionNo] [nvarchar](2) NULL,
	[CustomerNo] [nvarchar](20) NULL,
	[BillToName] [nvarchar](30) NULL,
	[BillToAddress1] [nvarchar](30) NULL,
	[BillToAddress2] [nvarchar](30) NULL,
	[BillToAddress3] [nvarchar](30) NULL,
	[BillToCity] [nvarchar](20) NULL,
	[BillToState] [nvarchar](2) NULL,
	[BillToZipCode] [nvarchar](10) NULL,
	[BillToCountryCode] [nvarchar](3) NULL,
	[ShipToCode] [nvarchar](4) NULL,
	[ShipToName] [nvarchar](30) NULL,
	[ShipToAddress1] [nvarchar](30) NULL,
	[ShipToAddress2] [nvarchar](30) NULL,
	[ShipToAddress3] [nvarchar](30) NULL,
	[ShipToCity] [nvarchar](20) NULL,
	[ShipToState] [nvarchar](2) NULL,
	[ShipToZipCode] [nvarchar](10) NULL,
	[ShipToCountryCode] [nvarchar](3) NULL,
	[ConfirmTo] [nvarchar](30) NULL,
	[Comment] [nvarchar](30) NULL,
	[TermsCode] [nvarchar](2) NULL,
	[TaxSchedule] [nvarchar](9) NULL,
	[TaxExemptNo] [nvarchar](15) NULL,
	[EmailAddress] [nvarchar](250) NULL,
	[PaymentType] [nvarchar](5) NULL,
	[OtherPaymentTypeRefNo] [nvarchar](24) NULL,
	[PaymentTypeCategory] [nvarchar](1) NULL,
	[TaxableSubjectToDiscount] [money] NULL,
	[TaxSubjToDiscPrcntOfTotSubjTo] [real] NULL,
	[DiscountRate] [real] NULL,
	[DiscountAmt] [money] NULL,
	[TaxableAmt] [money] NULL,
	[NonTaxableAmt] [money] NULL,
	[SalesTaxAmt] [money] NULL,
	[FreightAmt] [money] NULL,
	[DepositAmt] [money] NULL,
	[ReceivedDate] [datetime] NULL,
	[SentToSage] [datetime] NULL,
	[MagCompleteOrder] [bit] NULL,
	[CustomerPONo] [nvarchar](15) NULL,
	[ShipVia] [nvarchar](15) NULL,
	[OrderLength] [int] NULL,
 CONSTRAINT [PK_tblSalesOrderHeader] PRIMARY KEY CLUSTERED 
(
	[MagSalesOrderNo] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[tblSalesOrderDetail]    Script Date: 12/16/2014 16:41:36 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[tblSalesOrderDetail](
	[MagSalesOrderNo] [nvarchar](13) NOT NULL,
	[MagLineNo] [nvarchar](7) NOT NULL,
	[SalesOrderNo] [nvarchar](7) NULL,
	[LineKey] [nvarchar](6) NULL,
	[LineSeqNo] [nvarchar](14) NULL,
	[ItemCode] [nvarchar](30) NULL,
	[ItemType] [nvarchar](1) NULL,
	[ItemCodeDesc] [nvarchar](30) NULL,
	[ExtendedDescriptionKey] [nvarchar](10) NULL,
	[Discount] [nvarchar](1) NULL,
	[Commissionable] [nvarchar](1) NULL,
	[SubjectToExemption] [nvarchar](1) NULL,
	[WarehouseCode] [nvarchar](3) NULL,
	[Valuation] [nvarchar](1) NULL,
	[PriceLevel1] [nvarchar](1) NULL,
	[UnitOfMeasure] [nvarchar](4) NULL,
	[LotSerialFullyDistributed] [nvarchar](1) NULL,
	[PromiseDate] [date] NULL,
	[AliasItemNo] [nvarchar](30) NULL,
	[TaxClass] [nvarchar](2) NULL,
	[CommentText] [nvarchar](2048) NULL,
	[QuantityOrdered] [real] NULL,
	[QuantityShipped] [real] NULL,
	[QuantityBackordered] [real] NULL,
	[UnitCost] [real] NULL,
	[ExtensionAmt] [real] NULL,
	[UnitofMeasureConvFactor] [real] NULL,
	[LineDiscountPercent] [real] NULL,
	[LineWeight] [real] NULL,
	[ReceivedDate] [datetime] NULL,
	[SODetailAutoNbr] [int] IDENTITY(1,1) NOT NULL,
	[DropShip] [bit] NULL,
 CONSTRAINT [PK_tblSalesOrderDetail] PRIMARY KEY CLUSTERED 
(
	[MagSalesOrderNo] ASC,
	[MagLineNo] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[tblItem]    Script Date: 12/16/2014 16:41:36 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[tblItem](
	[SKU] [nvarchar](32) NOT NULL,
	[SKU_Name] [nvarchar](255) NULL,
	[ShortDesc] [nvarchar](255) NULL,
	[Price] [money] NULL,
	[Special_Price] [money] NULL,
	[Special_From] [date] NULL,
	[Special_To] [date] NULL,
	[Tax_Class_ID] [nvarchar](2) NULL,
	[Qty] [int] NULL,
	[Backorders] [nvarchar](1) NULL,
	[DateAdded] [date] NULL,
	[SentToMagento] [date] NULL,
	[SageItemNo] [nvarchar](50) NULL,
	[ShipWeight] [nvarchar](10) NULL,
	[StandardUnitPrice] [money] NULL,
	[Attribute_Set] [nvarchar](50) NULL,
 CONSTRAINT [PK_tblItem] PRIMARY KEY CLUSTERED 
(
	[SKU] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[tblCustomer]    Script Date: 12/16/2014 16:41:36 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[tblCustomer](
	[EMailAddress] [nvarchar](250) NOT NULL,
	[CustomerName] [nvarchar](30) NULL,
	[URLAddress] [nvarchar](50) NULL,
	[SortField] [nvarchar](10) NULL,
	[CustomerType] [nvarchar](4) NULL,
	[AddressLine1] [nvarchar](30) NULL,
	[AddressLine2] [nvarchar](30) NULL,
	[City] [nvarchar](20) NULL,
	[TaxSchedule] [nvarchar](9) NULL,
	[CountryCode] [nvarchar](3) NULL,
	[ZipCode] [nvarchar](10) NULL,
	[TelephoneNo] [nvarchar](17) NULL,
	[TelephoneExt] [nvarchar](5) NULL,
	[FaxNo] [nvarchar](17) NULL,
	[SentToMagento] [bit] NULL,
	[AddedDate] [date] NULL,
	[State] [nvarchar](2) NULL,
 CONSTRAINT [PK_tblCustomer] PRIMARY KEY CLUSTERED 
(
	[EMailAddress] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  View [dbo].[vw_SOAdjustments]    Script Date: 12/16/2014 16:41:36 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE VIEW [dbo].[vw_SOAdjustments]
AS
SELECT     MagSalesOrderNo AS [Magento Order Nbr], MagLineNo AS [Magento Line Nbr], LineKey AS [Magento Line Key], MagChange AS [Adjust Desc], ReceivedDate AS Received, AutoNbr AS [Unique Nbr], 
                      SageStatus AS [Sage Changes]
FROM         dbo.tblSOAdjustment
WHERE     (SageAdjusted = 0)
GO
EXEC sys.sp_addextendedproperty @name=N'MS_DiagramPane1', @value=N'[0E232FF0-B466-11cf-A24F-00AA00A3EFFF, 1.00]
Begin DesignProperties = 
   Begin PaneConfigurations = 
      Begin PaneConfiguration = 0
         NumPanes = 4
         Configuration = "(H (1[40] 4[20] 2[20] 3) )"
      End
      Begin PaneConfiguration = 1
         NumPanes = 3
         Configuration = "(H (1 [50] 4 [25] 3))"
      End
      Begin PaneConfiguration = 2
         NumPanes = 3
         Configuration = "(H (1 [50] 2 [25] 3))"
      End
      Begin PaneConfiguration = 3
         NumPanes = 3
         Configuration = "(H (4 [30] 2 [40] 3))"
      End
      Begin PaneConfiguration = 4
         NumPanes = 2
         Configuration = "(H (1 [56] 3))"
      End
      Begin PaneConfiguration = 5
         NumPanes = 2
         Configuration = "(H (2 [66] 3))"
      End
      Begin PaneConfiguration = 6
         NumPanes = 2
         Configuration = "(H (4 [50] 3))"
      End
      Begin PaneConfiguration = 7
         NumPanes = 1
         Configuration = "(V (3))"
      End
      Begin PaneConfiguration = 8
         NumPanes = 3
         Configuration = "(H (1[56] 4[18] 2) )"
      End
      Begin PaneConfiguration = 9
         NumPanes = 2
         Configuration = "(H (1 [75] 4))"
      End
      Begin PaneConfiguration = 10
         NumPanes = 2
         Configuration = "(H (1[66] 2) )"
      End
      Begin PaneConfiguration = 11
         NumPanes = 2
         Configuration = "(H (4 [60] 2))"
      End
      Begin PaneConfiguration = 12
         NumPanes = 1
         Configuration = "(H (1) )"
      End
      Begin PaneConfiguration = 13
         NumPanes = 1
         Configuration = "(V (4))"
      End
      Begin PaneConfiguration = 14
         NumPanes = 1
         Configuration = "(V (2))"
      End
      ActivePaneConfig = 0
   End
   Begin DiagramPane = 
      Begin Origin = 
         Top = 0
         Left = 0
      End
      Begin Tables = 
         Begin Table = "tblSOAdjustment"
            Begin Extent = 
               Top = 6
               Left = 38
               Bottom = 126
               Right = 460
            End
            DisplayFlags = 280
            TopColumn = 4
         End
      End
   End
   Begin SQLPane = 
   End
   Begin DataPane = 
      Begin ParameterDefaults = ""
      End
      Begin ColumnWidths = 9
         Width = 284
         Width = 1500
         Width = 1500
         Width = 1500
         Width = 1500
         Width = 1500
         Width = 1500
         Width = 1500
         Width = 1500
      End
   End
   Begin CriteriaPane = 
      Begin ColumnWidths = 11
         Column = 1440
         Alias = 900
         Table = 1170
         Output = 720
         Append = 1400
         NewValue = 1170
         SortType = 1350
         SortOrder = 1410
         GroupBy = 1350
         Filter = 1350
         Or = 1350
         Or = 1350
         Or = 1350
      End
   End
End
' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'VIEW',@level1name=N'vw_SOAdjustments'
GO
EXEC sys.sp_addextendedproperty @name=N'MS_DiagramPaneCount', @value=1 , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'VIEW',@level1name=N'vw_SOAdjustments'
GO
/****** Object:  Default [DF_tblCustomer_SentToMagento]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblCustomer] ADD  CONSTRAINT [DF_tblCustomer_SentToMagento]  DEFAULT ((0)) FOR [SentToMagento]
GO
/****** Object:  Default [DF_tblCustomer_AddedDate]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblCustomer] ADD  CONSTRAINT [DF_tblCustomer_AddedDate]  DEFAULT (getdate()) FOR [AddedDate]
GO
/****** Object:  Default [DF_tblItem_DateAdded]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblItem] ADD  CONSTRAINT [DF_tblItem_DateAdded]  DEFAULT (getdate()) FOR [DateAdded]
GO
/****** Object:  Default [DF_tblSalesOrderDetail_Discount]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderDetail] ADD  CONSTRAINT [DF_tblSalesOrderDetail_Discount]  DEFAULT (N'N') FOR [Discount]
GO
/****** Object:  Default [DF_tblSalesOrderDetail_QuantityOrdered]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderDetail] ADD  CONSTRAINT [DF_tblSalesOrderDetail_QuantityOrdered]  DEFAULT ((0)) FOR [QuantityOrdered]
GO
/****** Object:  Default [DF_tblSalesOrderDetail_QuantityShipped]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderDetail] ADD  CONSTRAINT [DF_tblSalesOrderDetail_QuantityShipped]  DEFAULT ((0)) FOR [QuantityShipped]
GO
/****** Object:  Default [DF_tblSalesOrderDetail_QuantityBackordered]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderDetail] ADD  CONSTRAINT [DF_tblSalesOrderDetail_QuantityBackordered]  DEFAULT ((0)) FOR [QuantityBackordered]
GO
/****** Object:  Default [DF_tblSalesOrderDetail_UnitCost]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderDetail] ADD  CONSTRAINT [DF_tblSalesOrderDetail_UnitCost]  DEFAULT ((0)) FOR [UnitCost]
GO
/****** Object:  Default [DF_tblSalesOrderDetail_ExtensionAmt]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderDetail] ADD  CONSTRAINT [DF_tblSalesOrderDetail_ExtensionAmt]  DEFAULT ((0)) FOR [ExtensionAmt]
GO
/****** Object:  Default [DF_tblSalesOrderDetail_UnitofMeasureConvFactor]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderDetail] ADD  CONSTRAINT [DF_tblSalesOrderDetail_UnitofMeasureConvFactor]  DEFAULT ((0)) FOR [UnitofMeasureConvFactor]
GO
/****** Object:  Default [DF_tblSalesOrderDetail_LineDiscountPercent]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderDetail] ADD  CONSTRAINT [DF_tblSalesOrderDetail_LineDiscountPercent]  DEFAULT ((0)) FOR [LineDiscountPercent]
GO
/****** Object:  Default [DF_tblSalesOrderDetail_LineWeight]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderDetail] ADD  CONSTRAINT [DF_tblSalesOrderDetail_LineWeight]  DEFAULT ((0)) FOR [LineWeight]
GO
/****** Object:  Default [DF_tblSalesOrderDetail_ReceivedDate]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderDetail] ADD  CONSTRAINT [DF_tblSalesOrderDetail_ReceivedDate]  DEFAULT (getdate()) FOR [ReceivedDate]
GO
/****** Object:  Default [DF_tblSalesOrderDetail_DropShip]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderDetail] ADD  CONSTRAINT [DF_tblSalesOrderDetail_DropShip]  DEFAULT ((0)) FOR [DropShip]
GO
/****** Object:  Default [DF_tblSalesOrderHeader_TaxableSubjectToDiscount]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderHeader] ADD  CONSTRAINT [DF_tblSalesOrderHeader_TaxableSubjectToDiscount]  DEFAULT ((0)) FOR [TaxableSubjectToDiscount]
GO
/****** Object:  Default [DF_tblSalesOrderHeader_TaxSubjToDiscPrcntOfTotSubjTo]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderHeader] ADD  CONSTRAINT [DF_tblSalesOrderHeader_TaxSubjToDiscPrcntOfTotSubjTo]  DEFAULT ((0)) FOR [TaxSubjToDiscPrcntOfTotSubjTo]
GO
/****** Object:  Default [DF_tblSalesOrderHeader_DiscountRate]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderHeader] ADD  CONSTRAINT [DF_tblSalesOrderHeader_DiscountRate]  DEFAULT ((0)) FOR [DiscountRate]
GO
/****** Object:  Default [DF_tblSalesOrderHeader_DiscountAmt]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderHeader] ADD  CONSTRAINT [DF_tblSalesOrderHeader_DiscountAmt]  DEFAULT ((0)) FOR [DiscountAmt]
GO
/****** Object:  Default [DF_tblSalesOrderHeader_TaxableAmt]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderHeader] ADD  CONSTRAINT [DF_tblSalesOrderHeader_TaxableAmt]  DEFAULT ((0)) FOR [TaxableAmt]
GO
/****** Object:  Default [DF_tblSalesOrderHeader_NonTaxableAmt]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderHeader] ADD  CONSTRAINT [DF_tblSalesOrderHeader_NonTaxableAmt]  DEFAULT ((0)) FOR [NonTaxableAmt]
GO
/****** Object:  Default [DF_tblSalesOrderHeader_SalesTaxAmt]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderHeader] ADD  CONSTRAINT [DF_tblSalesOrderHeader_SalesTaxAmt]  DEFAULT ((0)) FOR [SalesTaxAmt]
GO
/****** Object:  Default [DF_tblSalesOrderHeader_FreightAmt]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderHeader] ADD  CONSTRAINT [DF_tblSalesOrderHeader_FreightAmt]  DEFAULT ((0)) FOR [FreightAmt]
GO
/****** Object:  Default [DF_tblSalesOrderHeader_DepositAmt]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderHeader] ADD  CONSTRAINT [DF_tblSalesOrderHeader_DepositAmt]  DEFAULT ((0)) FOR [DepositAmt]
GO
/****** Object:  Default [DF_tblSalesOrderHeader_ReceivedDate]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderHeader] ADD  CONSTRAINT [DF_tblSalesOrderHeader_ReceivedDate]  DEFAULT (getdate()) FOR [ReceivedDate]
GO
/****** Object:  Default [DF_tblSalesOrderHeader_MagCompleteOrder]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderHeader] ADD  CONSTRAINT [DF_tblSalesOrderHeader_MagCompleteOrder]  DEFAULT ((0)) FOR [MagCompleteOrder]
GO
/****** Object:  Default [DF_tblSalesOrderHeader_OrderLength]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSalesOrderHeader] ADD  CONSTRAINT [DF_tblSalesOrderHeader_OrderLength]  DEFAULT ((0)) FOR [OrderLength]
GO
/****** Object:  Default [DF_tblShippedTracking_SentToMagento]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblShippedTracking] ADD  CONSTRAINT [DF_tblShippedTracking_SentToMagento]  DEFAULT ((0)) FOR [SentToMagento]
GO
/****** Object:  Default [DF_tblSOAdjustment_ReceivedDate]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSOAdjustment] ADD  CONSTRAINT [DF_tblSOAdjustment_ReceivedDate]  DEFAULT (getdate()) FOR [ReceivedDate]
GO
/****** Object:  Default [DF_tblSOAdjustment_SageAdjusted]    Script Date: 12/16/2014 16:41:36 ******/
ALTER TABLE [dbo].[tblSOAdjustment] ADD  CONSTRAINT [DF_tblSOAdjustment_SageAdjusted]  DEFAULT ((0)) FOR [SageAdjusted]
GO
